const LOG_ENTRY_SELECTOR = '.log-entry';

function setButtonState(button, isActive) {
    button.classList.toggle('log-tab-button--active', isActive);
    button.classList.toggle('log-tab-button--inactive', !isActive);
}

function formatCompositeHint(value) {
    if (Array.isArray(value)) {
        return `[${value.length}]`;
    }

    return `{${Object.keys(value).length}}`;
}

function createValueNode(value) {
    const span = document.createElement('span');

    if (value === null) {
        span.className = 'json-tree-null';
        span.textContent = 'null';
        return span;
    }

    if (typeof value === 'string') {
        span.className = 'json-tree-string';
        span.textContent = `"${value}"`;
        return span;
    }

    if (typeof value === 'number') {
        span.className = 'json-tree-number';
        span.textContent = String(value);
        return span;
    }

    if (typeof value === 'boolean') {
        span.className = 'json-tree-boolean';
        span.textContent = value ? 'true' : 'false';
        return span;
    }

    span.className = 'json-tree-string';
    span.textContent = String(value);
    return span;
}

function createLeafNode(label, value) {
    const row = document.createElement('div');
    const key = document.createElement('span');

    row.className = 'json-tree-leaf';
    key.className = 'json-tree-key';
    key.textContent = `${label}:`;

    row.appendChild(key);
    row.appendChild(createValueNode(value));

    return row;
}

function createBranchNode(label, value, depth = 0) {
    if (value === null || typeof value !== 'object') {
        return createLeafNode(label, value);
    }

    const isArray = Array.isArray(value);
    const entries = isArray ? value.map((item, index) => [index, item]) : Object.entries(value);

    if (entries.length === 0) {
        return createLeafNode(label, isArray ? '[]' : '{}');
    }

    const details = document.createElement('details');
    const summary = document.createElement('summary');
    const key = document.createElement('span');
    const hint = document.createElement('span');
    const children = document.createElement('div');

    details.className = 'json-tree-branch';
    details.open = depth < 1;

    summary.className = 'json-tree-summary';

    key.className = 'json-tree-key';
    key.textContent = `${label}:`;

    hint.className = 'json-tree-hint';
    hint.textContent = formatCompositeHint(value);

    summary.appendChild(key);
    summary.appendChild(hint);

    children.className = 'json-tree-children';

    entries.forEach(([childKey, childValue]) => {
        children.appendChild(createBranchNode(childKey, childValue, depth + 1));
    });

    details.appendChild(summary);
    details.appendChild(children);

    return details;
}

function createJsonTree(textValue) {
    let parsed;

    try {
        parsed = JSON.parse(textValue);
    } catch (error) {
        const emptyState = document.createElement('div');
        emptyState.className = 'json-tree-empty';
        emptyState.textContent = 'Tree view is only available for valid JSON payloads.';
        return {
            element: emptyState,
            expandAll: null,
            collapseAll: null,
        };
    }

    const viewer = document.createElement('div');
    const toolbar = document.createElement('div');
    const expandButton = document.createElement('button');
    const collapseButton = document.createElement('button');
    const treeRoot = document.createElement('div');

    viewer.className = 'json-viewer';

    toolbar.className = 'json-viewer-toolbar';

    expandButton.type = 'button';
    expandButton.className = 'json-viewer-button';
    expandButton.textContent = 'Expand All';

    collapseButton.type = 'button';
    collapseButton.className = 'json-viewer-button';
    collapseButton.textContent = 'Collapse All';

    treeRoot.className = 'json-tree-root';
    treeRoot.appendChild(createBranchNode('root', parsed));

    expandButton.addEventListener('click', function() {
        treeRoot.querySelectorAll('details').forEach((details) => {
            details.open = true;
        });
    });

    collapseButton.addEventListener('click', function() {
        treeRoot.querySelectorAll('details').forEach((details) => {
            details.open = false;
        });

        const rootNode = treeRoot.querySelector('details');
        if (rootNode) {
            rootNode.open = true;
        }
    });

    toolbar.appendChild(expandButton);
    toolbar.appendChild(collapseButton);
    viewer.appendChild(toolbar);
    viewer.appendChild(treeRoot);

    return {
        element: viewer,
        expandAll: expandButton,
        collapseAll: collapseButton,
    };
}

function enhanceLogEntry(entry) {
    if (!(entry instanceof HTMLElement) || entry.dataset.jsonTabsReady === 'true') {
        return;
    }

    const textarea = entry.querySelector('.log-textarea');
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    const tabs = document.createElement('div');
    const rawButton = document.createElement('button');
    const treeButton = document.createElement('button');
    const rawPanel = document.createElement('div');
    const treePanel = document.createElement('div');
    const treeView = createJsonTree(textarea.value);

    entry.dataset.jsonTabsReady = 'true';

    tabs.className = 'log-tabs';

    rawButton.type = 'button';
    rawButton.className = 'log-tab-button';
    rawButton.textContent = 'Raw';

    treeButton.type = 'button';
    treeButton.className = 'log-tab-button';
    treeButton.textContent = 'Tree';

    rawPanel.className = 'mt-4';
    treePanel.className = 'mt-4 hidden';

    rawPanel.appendChild(textarea);
    treePanel.appendChild(treeView.element);

    function activateTab(tabName) {
        const isRaw = tabName === 'raw';

        rawPanel.classList.toggle('hidden', !isRaw);
        treePanel.classList.toggle('hidden', isRaw);
        setButtonState(rawButton, isRaw);
        setButtonState(treeButton, !isRaw);
    }

    rawButton.addEventListener('click', function() {
        activateTab('raw');
    });

    treeButton.addEventListener('click', function() {
        activateTab('tree');
    });

    tabs.appendChild(rawButton);
    tabs.appendChild(treeButton);
    entry.appendChild(tabs);
    entry.appendChild(rawPanel);
    entry.appendChild(treePanel);

    activateTab('raw');
}

function initializeJsonLogTabs(scope = document) {
    scope.querySelectorAll(LOG_ENTRY_SELECTOR).forEach((entry) => {
        enhanceLogEntry(entry);
    });
}

function observeJsonLogContainers() {
    document.querySelectorAll('#apiResponseLogs').forEach((container) => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }

                    if (node.matches(LOG_ENTRY_SELECTOR)) {
                        enhanceLogEntry(node);
                    }

                    node.querySelectorAll?.(LOG_ENTRY_SELECTOR).forEach((entry) => {
                        enhanceLogEntry(entry);
                    });
                });
            });
        });

        observer.observe(container, { childList: true, subtree: true });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeJsonLogTabs();
    observeJsonLogContainers();
});

window.AppJsonLogs = {
    enhanceLogEntry,
    initializeJsonLogTabs,
};
