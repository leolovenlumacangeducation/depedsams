// Document management feature disabled: provide a safe no-op stub
const DocumentManager = {
    currentItem: null,
    setCurrentItem(item) {
        console.warn('DocumentManager: feature disabled. setCurrentItem ignored.');
    },
    init() {
        console.warn('DocumentManager: feature disabled. init skipped.');
    }
};
window.DocumentManager = DocumentManager;