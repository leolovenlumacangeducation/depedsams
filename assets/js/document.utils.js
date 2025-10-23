/* document.utils.js (stub)
 * Document utilities disabled. This file replaces the original document utilities
 * and provides safe no-op implementations so pages that reference these
 * functions do not throw errors when the document feature is disabled.
 */

function printDocument(contentId, title = 'Print Document') {
    console.warn('printDocument: document feature disabled. No action taken.');
}

async function downloadDocumentAsPdf(contentId, defaultFilename, numberSelector) {
    console.warn('downloadDocumentAsPdf: document feature disabled. No action taken.');
}

function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    const p = document.createElement('p');
    p.textContent = String(str);
    return p.innerHTML;
}
