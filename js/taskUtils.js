// js/taskUtils.js

window.calcularPosicionCursor = function(ev, el) {
    const sel = window.getSelection();
    sel.removeAllRanges();

    const rango = document.createRange();
    rango.selectNodeContents(el);
    rango.collapse(true);

    const puntoClic = document.caretPositionFromPoint(ev.clientX, ev.clientY);
    if (puntoClic) {
        sel.setBaseAndExtent(puntoClic.offsetNode, puntoClic.offset, puntoClic.offsetNode, puntoClic.offset);
        return puntoClic.offset;
    }

    return 0;
}

window.setCursorPos = function(el, off) {
    const sel = window.getSelection();
    const rango = document.createRange();

    if (el.firstChild) {
        rango.setStart(el.firstChild, off);
        rango.setEnd(el.firstChild, off);
    } else {
        rango.setStart(el, 0);
        rango.setEnd(el, 0);
    }

    rango.collapse(true);
    sel.removeAllRanges();
    sel.addRange(rango);
}
