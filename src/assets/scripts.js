function TextBlockController() { return {
    handleChange(event) {
        const clone = this.$el.cloneNode(true)
        const form = document.createElement('form')
        form.appendChild(clone)
        const body = new FormData(form)
        body.append(window.csrfTokenName, window.csrfTokenValue)
        fetch(`${window.iglooCpUrl}/blocks/upsert`, {method: 'POST', body})
            .then(res => res.text())
            .then(body => console.log('BODY', body))
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
            .then(({components}) => {
                for (const selector in components) {
                    const elements = Array.from(document.querySelectorAll(selector))
                    elements.forEach(el => {
                        el.outerHTML = components[selector]
                    })
                }
            })
            .catch(err => console.log('ERROR', err))
    }
}}
