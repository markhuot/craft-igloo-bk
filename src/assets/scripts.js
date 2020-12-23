function debugAjaxResponse(data) {
    alert(JSON.stringify(data))
    return data
}

const hiddenClassNames = ['translate-y-4', 'md:translate-y-0', 'md:translate-x-4', 'opacity-0']

function walkDom(el, callback) {
    callback(el)

    el = el.firstChild
    while (el) {
        walkDom(el, callback)
        el = el.nextSibling
    }
}

function handleAjaxResponse({components, panel, action}) {
    for (const selector in components) {
        const elements = Array.from(document.querySelectorAll(selector))
        elements.forEach(el => {
            // explore patching the DOM instead
            // https://github.com/snabbdom/snabbdom
            // https://github.com/patrick-steele-idem/morphdom
            walkDom(el, (e) => {
                // We need to set a bogus data attribute to make sure that this
                // element is considered by morphdom. Because `data-bogus` is set
                // client side to a unique value it will always track as "changed"
                // and then in our morphdom constructor we can ensure we copy over
                // any checked/selected props
                if (e.hasAttribute && e.hasAttribute('data-morphdom-skip')) {
                    e.setAttribute('data-bogus', Math.random())
                }
            })
            window.patch(el, components[selector])
            //el.outerHTML = components[selector]
        })
    }

    if (panel) {
        const otherPanels = Array.from(document.querySelectorAll('[data-panel]'))
        otherPanels.forEach(panel => panel.classList.add(...hiddenClassNames))

        const tmp = document.createElement('div')
        tmp.innerHTML = panel
        const panelEl = tmp.firstChild
        panelEl.classList.add(...hiddenClassNames)
        document.body.appendChild(panelEl)
        setTimeout(() => panelEl.classList.remove(...hiddenClassNames), 1)
    }

    if (action === 'back') {
        const panels = Array.from(document.querySelectorAll('[data-panel'))
        if (panels.length) {
            popPanel()
        }
    }
}

function popPanel() {
    const panels = Array.from(document.querySelectorAll('[data-panel]'))
    if (panels.length === 0) {
        return
    }

    panels[panels.length - 1].addEventListener('transitionend', event => {
        if (event.propertyName !== 'opacity') {
            return
        }

        event.target.parentNode.removeChild(event.target)
    })
    panels[panels.length - 1].classList.add(...hiddenClassNames)
    panels[panels.length - 2].classList.remove(...hiddenClassNames)
}

function PanelBackController() { return {
    back(event) {
        event.preventDefault()
        //const panel = this.$el.parentNode.closest('[data-panel]')
        //panel.parentNode.removeChild(panel)
        popPanel()
    }
}}

function LayersController() { return {
    handleAddClick(event) {
        event.preventDefault()
        fetch(`${window.iglooCpUrl}/tree/${this.treeId}/add-layer`)
            .then(res => res.json())
            .then(handleAjaxResponse)
    }
}}

function LayerController() { return {
    handleStylesClick(event) {
        event.preventDefault()
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}/styles`)
            .then(res => res.json())
            .then(handleAjaxResponse)
    },
    handleActionClick(event) {
        event.preventDefault()
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}/actions?path=${this.blockPath}`)
            .then(res => res.json())
            .then(handleAjaxResponse)
    }
}}

function BlockActionController() { return {
    handleDelete(event) {
        event.preventDefault()
        if (confirm('Are you sure you want to delete this block?')) {
            const body = new FormData(document.createElement('form'))
            body.append('_method', 'DELETE')
            body.append(window.csrfTokenName, window.csrfTokenValue)
            fetch(`${window.iglooCpUrl}/blocks/${this.blockId}`, {method: 'POST', body})
                .then(res => res.json())
                .then(handleAjaxResponse)
                .catch(err => console.log('ERROR', err))
        }
    },
    handleInsert(event) {
        event.preventDefault()
        const placement = event.currentTarget.value === 'before' ? 'before' : 'after'
        fetch(`${window.iglooCpUrl}/tree/${this.treeId}/add-layer?placement=${placement}&path=${this.path}`)
            .then(res => res.json())
            .then(handleAjaxResponse)
    },
}}

function elementIndex(element) {
    let index = 0

    while (element = element.previousElementSibling) {
        index += 1
    }
    
    return index
}

// for iphone: https://www.npmjs.com/package/drag-drop-touch
function LayerDragController() { return {
    handleDragStart(event) {
        event.stopPropagation()
        event.dataTransfer.setData("text/plain", "foo bar")
        event.dataTransfer.setData("application/igloo", JSON.stringify({
            blockId: event.currentTarget.dataset.blockId, 
            sourcePath: event.currentTarget.dataset.blockPath,
        }))
        event.dataTransfer.dropEffect = "move"
    },
    handleDragOver(event) {
        event.preventDefault()
        event.dataTransfer.dropEffect = "move"
    },
    handleDrop(event) {
        event.preventDefault()
        event.stopPropagation()
        const {blockId, sourcePath} = JSON.parse(event.dataTransfer.getData("application/igloo"))
        let destinationPath = event.currentTarget.dataset.blockPath
        const tree = event.currentTarget.dataset.tree
        const body = new FormData(document.createElement('form'))
        body.append(window.csrfTokenName, window.csrfTokenValue)
        const {height} = event.currentTarget.getBoundingClientRect()
        const offsetY = event.offsetY
        const after = offsetY > height / 2
        if (after && !event.currentTarget.dataset.blockIsEmptyPlaceholder) {
            const destinationSegments = destinationPath
                .split('.')
                .map((seg, index) => index%2==0 ? parseInt(seg) : seg)
            destinationSegments[destinationSegments.length - 1] += 1
            destinationPath = destinationSegments.join('.')
        }
        body.append('sourcePath', sourcePath)
        body.append('destinationPath', destinationPath)
        // alert(JSON.stringify({sourcePath, destinationPath}))
        fetch(`${window.iglooCpUrl}/tree/${tree}/move/${blockId}`, {method: 'POST', body})
            .then(res => res.json())
            // .then(debugAjaxResponse)
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    }
}}

function AddLayerController() { return {
    handleFormSubmit(event) {
        event.preventDefault()
        const action = this.$el.action
        const method = this.$el.method
        const body = new FormData(this.$el)
        fetch(action, {method, body})
            .then(res => res.json())
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    }
}}

function TextBlockController() { return {
    init() {
        // init text editorn with this.$refs.input
    },
    handleChange(event) {
        const clone = this.$el.cloneNode(true)
        const form = document.createElement('form')
        form.appendChild(clone)
        const body = new FormData(form)
        body.append(window.csrfTokenName, window.csrfTokenValue)
        const prop = event.currentTarget.getAttribute('name')
        const value = event.currentTarget.innerHTML
        body.append(prop, value)
        fetch(`${window.iglooCpUrl}/blocks/upsert`, {method: 'POST', body})
            .then(res => res.json())
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    }
}}

function StylePanel() { return {
    handleChange(event) {
        const prop = event.target.name
        if (!prop) {
            return
        }

        const value = event.target.value
        const body = new FormData(document.createElement('form'))
        body.append(prop, value)
        body.append(window.csrfTokenName, window.csrfTokenValue)
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}/styles`, {method: 'POST', body})
            .then(res => res.json())
            // .then(debugAjaxResponse)
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    },
}}
