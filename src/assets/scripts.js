function handleAjaxResponse({components}) {
    for (const selector in components) {
        const elements = Array.from(document.querySelectorAll(selector))
        elements.forEach(el => {
            el.outerHTML = components[selector]
        })
    }
}

function PanelBackController() { return {
    back(event) {
        const panel = this.$el.parentNode.closest('[x-data]')
        panel.parentNode.removeChild(panel)
    }
}}

function LayersController() { return {
    handleAddClick(event) {
        fetch(`${window.iglooCpUrl}/tree/${this.tree}/add-layer`)
            .then(res => res.text())
            .then(html => document.body.innerHTML += html)
    }
}}

function LayerController() { return {
    handleStylesClick(event) {
        event.preventDefault()
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}/styles`)
            .then(res => res.text())
            .then(html => document.body.innerHTML += html)
    }
}}

function AddLayerController() { return {
    handleFormSubmit(event) {
        event.preventDefault()
        const action = 
        fetch(`${window.iglooCpUrl}/`)
    }
}}

function TextBlockController() { return {
    handleChange(event) {
        const clone = this.$el.cloneNode(true)
        const form = document.createElement('form')
        form.appendChild(clone)
        const body = new FormData(form)
        body.append(window.csrfTokenName, window.csrfTokenValue)
        fetch(`${window.iglooCpUrl}/blocks/upsert`, {method: 'POST', body})
            .then(res => res.json())
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    }
}}

function StylePanel() { return {
    handleChange(event) {
        const prop = event.target.name
        const value = event.target.value
        const body = new FormData(document.createElement('form'))
        body.append(prop, value)
        body.append(window.csrfTokenName, window.csrfTokenValue)
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}/styles`, {method: 'POST', body})
            .then(res => res.json())
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    },
    handleDelete(event) {
        event.preventDefault()
        const body = new FormData(document.createElement('form'))
        body.append('_method', 'DELETE')
        body.append(window.csrfTokenName, window.csrfTokenValue)
        fetch(`${window.iglooCpUrl}/blocks/${this.blockId}`, {method: 'POST', body})
            .then(res => res.json())
            .then(handleAjaxResponse)
            .catch(err => console.log('ERROR', err))
    }
}}
