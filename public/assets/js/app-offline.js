/**
 * Projeto Repack - Gerenciador Offline-First (IndexedDB & Background Sync)
 * Baseado estritamente em docs/FSD.md e AGENTS.md
 */

const RepackOffline = (() => {
    const DB_NAME = 'RepackDB';
    const DB_VERSION = 1;
    let db = null;

    // Inicialização do Banco de Dados IndexedDB
    const openDatabase = () => {
        return new Promise((resolve, reject) => {
            if (db) {
                resolve(db);
                return;
            }

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const dbInstance = event.target.result;

                // Tabela local de B.O.s offline
                if (!dbInstance.objectStoreNames.contains('apontamentos_bo')) {
                    const boStore = dbInstance.createObjectStore('apontamentos_bo', { keyPath: 'local_id', autoIncrement: true });
                    boStore.createIndex('synced', 'synced', { unique: false });
                    boStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Tabela local de Produtividade offline
                if (!dbInstance.objectStoreNames.contains('apontamentos_produtividade')) {
                    const prodStore = dbInstance.createObjectStore('apontamentos_produtividade', { keyPath: 'local_id', autoIncrement: true });
                    prodStore.createIndex('synced', 'synced', { unique: false });
                    prodStore.createIndex('created_at', 'created_at', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                db = event.target.result;
                resolve(db);
            };

            request.onerror = (event) => {
                console.error('Falha ao abrir IndexedDB:', event.target.error);
                reject(event.target.error);
            };
        });
    };

    // Atualiza badges visuais de status Online/Offline na barra superior
    const updateNetworkStatus = () => {
        const isOnline = navigator.onLine;
        const badges = document.querySelectorAll('.status-badge');
        badges.forEach(badge => {
            if (isOnline) {
                badge.className = 'status-badge online';
                badge.innerHTML = '● Conectado';
            } else {
                badge.className = 'status-badge offline';
                badge.innerHTML = '● Modo Offline';
            }
        });

        if (isOnline) {
            sincronizarPendentes();
        }
    };

    // Salvar B.O. localmente no IndexedDB
    const salvarBOLocal = async (dados) => {
        const database = await openDatabase();
        return new Promise((resolve, reject) => {
            const tx = database.transaction('apontamentos_bo', 'readwrite');
            const store = tx.objectStore('apontamentos_bo');
            const registro = {
                ...dados,
                synced: 0,
                origem_sincronizacao: 'offline',
                created_at: new Date().toISOString()
            };
            const req = store.add(registro);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    };

    // Salvar Produtividade localmente no IndexedDB
    const salvarProdutividadeLocal = async (dados) => {
        const database = await openDatabase();
        return new Promise((resolve, reject) => {
            const tx = database.transaction('apontamentos_produtividade', 'readwrite');
            const store = tx.objectStore('apontamentos_produtividade');
            const registro = {
                ...dados,
                synced: 0,
                origem_sincronizacao: 'offline',
                created_at: new Date().toISOString()
            };
            const req = store.add(registro);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    };

    // Submissão de B.O. (Online com Fallback Offline)
    const salvarBO = async (dados) => {
        if (!navigator.onLine) {
            await salvarBOLocal(dados);
            return { status: 'offline', message: 'Salvo localmente (offline)' };
        }

        try {
            const response = await fetch('/api/bo/salvar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            if (!response.ok) {
                throw new Error('Falha no servidor ao salvar B.O.');
            }

            return await response.json();
        } catch (error) {
            console.warn('Rede instável. Gravando B.O. no IndexedDB:', error);
            await salvarBOLocal(dados);
            return { status: 'offline_fallback', message: 'Conexão oscilou. Salvo localmente com segurança.' };
        }
    };

    // Submissão de Produtividade (Online com Fallback Offline)
    const salvarProdutividade = async (dados) => {
        if (!navigator.onLine) {
            await salvarProdutividadeLocal(dados);
            return { status: 'offline', message: 'Salvo localmente (offline)' };
        }

        try {
            const response = await fetch('/api/produtividade/salvar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            if (!response.ok) {
                throw new Error('Falha no servidor ao salvar Produtividade.');
            }

            return await response.json();
        } catch (error) {
            console.warn('Rede instável. Gravando Produtividade no IndexedDB:', error);
            await salvarProdutividadeLocal(dados);
            return { status: 'offline_fallback', message: 'Conexão oscilou. Salvo localmente com segurança.' };
        }
    };

    // Sincronização em Lote de B.O.s e Produtividade
    const sincronizarPendentes = async () => {
        if (!navigator.onLine) return;

        try {
            const database = await openDatabase();

            // Sincroniza B.O.s pendentes
            const txBO = database.transaction('apontamentos_bo', 'readonly');
            const storeBO = txBO.objectStore('apontamentos_bo');
            const reqBO = storeBO.getAll();

            reqBO.onsuccess = async () => {
                const pendentesBO = (reqBO.result || []).filter(item => item.synced === 0);
                if (pendentesBO.length > 0) {
                    try {
                        const resp = await fetch('/api/bo/sincronizar.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ apontamentos: pendentesBO })
                        });
                        if (resp.ok) {
                            const resJson = await resp.json();
                            if (resJson.status === 'success') {
                                const delTx = database.transaction('apontamentos_bo', 'readwrite');
                                const delStore = delTx.objectStore('apontamentos_bo');
                                pendentesBO.forEach(item => delStore.delete(item.local_id));
                            }
                        }
                    } catch (e) {
                        console.warn('Erro ao sincronizar B.O.s pendentes:', e);
                    }
                }
            };

            // Sincroniza Produtividade pendente
            const txProd = database.transaction('apontamentos_produtividade', 'readonly');
            const storeProd = txProd.objectStore('apontamentos_produtividade');
            const reqProd = storeProd.getAll();

            reqProd.onsuccess = async () => {
                const pendentesProd = (reqProd.result || []).filter(item => item.synced === 0);
                if (pendentesProd.length > 0) {
                    try {
                        const resp = await fetch('/api/produtividade/sincronizar.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ apontamentos: pendentesProd })
                        });
                        if (resp.ok) {
                            const resJson = await resp.json();
                            if (resJson.status === 'success') {
                                const delTx = database.transaction('apontamentos_produtividade', 'readwrite');
                                const delStore = delTx.objectStore('apontamentos_produtividade');
                                pendentesProd.forEach(item => delStore.delete(item.local_id));
                            }
                        }
                    } catch (e) {
                        console.warn('Erro ao sincronizar produtividade pendente:', e);
                    }
                }
            };
        } catch (err) {
            console.error('Erro na sincronização em lote:', err);
        }
    };

    // Inicialização de Listeners
    const init = () => {
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        document.addEventListener('DOMContentLoaded', () => {
            updateNetworkStatus();
            openDatabase().then(() => {
                sincronizarPendentes();
            });
        });

        // Registro do Service Worker se suportado
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => {
                    console.log('Service Worker registrado:', reg.scope);
                })
                .catch(err => {
                    console.warn('Falha ao registrar Service Worker:', err);
                });
        }
    };

    return {
        init,
        salvarBO,
        salvarProdutividade,
        sincronizarPendentes
    };
})();

RepackOffline.init();
