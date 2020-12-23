// @ts-ignore
import morphdom from 'morphdom/dist/morphdom-esm'

(<any> window).patch = (from: HTMLElement, to: string) => {
  morphdom(from, to, {
    getNodeKey(node: HTMLElement) {
      if (node.getAttribute) {
        return node.getAttribute('data-key')
      }
    },
    onBeforeElUpdated(a: HTMLElement, b: HTMLElement) {
      console.log(a.outerHTML)
      if (a.hasAttribute('data-morphdom-skip')) {
        (<HTMLInputElement> b).checked = (<HTMLInputElement> a).checked
        //return false
      }
    },
    onBeforeElChildrenUpdated(a: HTMLElement, b: HTMLElement) {
      //console.log(a.outerHTML)
    },
  })
}


/*import { init } from 'snabbdom/build/package/init'
import { Module } from 'snabbdom/build/package/modules/module'
import { attributesModule } from "snabbdom/build/package/modules/attributes"
import { toVNode } from "snabbdom/build/package/tovnode"
import { VNode } from 'snabbdom/build/package/vnode'

const doIt = (oldVNode: VNode, vNode: VNode) => {
    // const pre = document.createElement('pre')
    // pre.innerHTML = JSON.stringify(oldVNode)
    // document.body.append(pre)
    // console.log(JSON.stringify({sel: oldVNode.sel, attrs:oldVNode?.data?.attrs}))
    // if (oldVNode?.data?.dataset?.vnodeIgnore == '1') {
    //     return
    // }
    console.log((<Element> vNode.elm!).outerHTML)

    return attributesModule.create(oldVNode, vNode)
}
const myAttributesModule: Module = {
    create: doIt,
    update: doIt,
}

const patch = init([
    myAttributesModule,
]);

(<any> window).patch = (el: Element, html: string) => {
    const htmlNode = document.createElement('div')
    htmlNode.innerHTML = html
    const vNode = toVNode(htmlNode.firstChild)
    // @TODO debug old and figure out why the disclosure input[type="checkbox"] is not 
    //       visible to me in the doIt when comparing oldVNode and vNode
    const old = toVNode(el)
    patch(old, vNode)
}*/

/*import {EditorState} from "prosemirror-state"
import {EditorView} from "prosemirror-view"
import {Schema, DOMParser} from "prosemirror-model"
import {schema} from "prosemirror-schema-basic"
import {addListNodes} from "prosemirror-schema-list"
import {exampleSetup} from "prosemirror-example-setup"

// Mix the nodes from prosemirror-schema-list into the basic schema to
// create a schema with list support.
const mySchema = new Schema({
    nodes: addListNodes(schema.spec.nodes, "paragraph block*", "block"),
    marks: schema.spec.marks
})
new EditorView(document.querySelector("#editor"), {
    state: EditorState.create({
        doc: DOMParser.fromSchema(mySchema).parse(document.querySelector("#content")),
        plugins: exampleSetup({schema: mySchema})
    })
})*/
