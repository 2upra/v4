//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A03 = false; // Cambia a true para activar los logs

const log03 = A03 ? log03 : function () {};
//////////////////////////////////////////////


function initializeFormFunctions() {

    const postTagsList = ['acoustic', 'chord', 'dry', 'harmony', 'loop', 'melody', 'mixed', 'monophonic', 'one shot', 'polyphonic', 'processed', 'progression', 'riser/sweep', 'short', 'timbre','bassy', 'boomy', 'breathy', 'bright', 'clean', 'cold', 'dark', 'delicate', 'detuned', 'dissonant', 'distorted', 'exotic', 'full', 'glitchy', 'granular', 'hard', 'high', 'hollow', 'low', 'metallic', 'muffled', 'muted', 'narrow', 'noisy', 'sizzling', 'smooth', 'soft', 'ambient', 'breaks', 'chillout', 'chiptune', 'cinematic', 'classical', 'Electro', 'electro swing', 'folk/country', 'funk/soul', 'jazz', 'jungle', 'House', 'Hip Hop', 'tech house', 'arpeggiated', 'decaying', 'echoing', 'long release', 'legato', 'glissando/glide', 'pad', 'percussive', 'plucked', 'pulsating', 'punchy', 'randomized', 'straight', 'sustained', 'syncopated', 'uptempo', 'wobble', 'vibrato', 'analog', 'compressed', 'digital', 'dynamic', 'loud','range', 'female', 'funky', 'jazzy', 'lo fi', 'male', 'quiet', 'vintage', 'vinyl', 'aggressive', 'angry', 'bouncy', 'calming', 'cheerful', 'climactic', 'cool', 'dramatic', 'elegant', 'epic', 'excited', 'energetic', 'fun', 'futuristic', 'gentle', 'groovy', 'happy', 'haunting', 'hypnotic', 'industrial', 'manic', 'melancholic', 'mellow', 'mystical', 'nervous', 'passionate', 'peaceful', 'playful', 'powerful', 'rebellious', 'reflective', 'relaxing', 'romantic', 'sad', 'sentimental', 'sexy', 'sophisticated', 'suspenseful', 'uplifting', 'urgent', 'weird'].map(t => t.toLowerCase());

    setupTagSystem({
        containerId: 'postTags1',
        maxTags: 20,
        minLength: 3,
        maxLength: 40,
        tagClass: 'tag'
    });
    const getContent = TagEnTexto({
        containerId: 'textoRs',
        maxTags: 20,
        minLength: 2,
        maxLength: 40
    });    

} 


function setupTagSystem(options) {
    const {
        containerId = 'postTags1',
        maxTags = 20,
        minLength = 2,
        maxLength = 40,
        whitelist = [],
        tagClass = 'tag'
    } = options;

    log03(`Procesando containerId: ${containerId}, maxTags: ${maxTags}, minLength: ${minLength}, maxLength: ${maxLength}, whitelist: ${whitelist}, tagClass: ${tagClass}`);

    const container = document.getElementById(containerId);
    if (!container) {
        //console.error(`Container with id ${containerId} not found`);
        return;
    } else {
        //log03(`Container with id ${containerId} found successfully`);
    }
    
    let tags = [];

    container.addEventListener('input', handleInput);
    container.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleDocumentClick);

    function handleInput(event) {
        if (event.inputType === 'insertText' && event.data === ' ') {
            processTags(container);
        }
    }

    function handleKeyDown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            processTags(container);
        }
    }

    function handleDocumentClick(event) {
        if (!container.contains(event.target)) {
            processTags(container, false);
        }
    }

    function processTags(container, shouldFocus = true) {
        const childNodes = Array.from(container.childNodes);
        const existingTags = childNodes.filter(node => node.nodeType === Node.ELEMENT_NODE && node.classList.contains(tagClass));
        const textNodes = childNodes.filter(node => node.nodeType === Node.TEXT_NODE);
        const newContent = textNodes.map(node => node.textContent.trim()).join(' ');
    
        tags = existingTags.map(tagSpan => tagSpan.textContent);
    
        const newTags = newContent.split(/[,\s]+/).filter(tag => tag.length > 0);
    
        newTags.forEach(tag => {
            let cleanTag = tag.replace(/[^\w\s]/g, '').trim();
            if (cleanTag && isValidTag(cleanTag) && !tags.includes(cleanTag)) {
                tags.push(cleanTag);
                
                const tagSpan = document.createElement('span');
                tagSpan.className = tagClass;
                tagSpan.textContent = cleanTag;
                tagSpan.contentEditable = false;
                container.appendChild(tagSpan);
                const spaceSpan = document.createElement('span');
                spaceSpan.className = 'tag-space';
                spaceSpan.textContent = '\u00A0';
                spaceSpan.contentEditable = false;
                container.appendChild(spaceSpan);
            }
        });
    
        textNodes.forEach(node => node.remove());
    
        container.appendChild(document.createTextNode('\u200B'));
    
        if (shouldFocus) {
            placeCaretAtEnd(container);
            container.focus();
        }
    }

    function isValidTag(tag) {
        return tag.length >= minLength && tag.length <= maxLength && tags.length < maxTags && (whitelist.length === 0 || whitelist.includes(tag));
    }

    function placeCaretAtEnd(el) {
        el.focus();
        if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
            var range = document.createRange();
            range.selectNodeContents(el);
            range.collapse(false);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (typeof document.body.createTextRange != "undefined") {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.collapse(false);
            textRange.select();
        }
    }
}

function TagEnTexto(options) {
    const {
        containerId = 'textoRs',
        maxTags = 20,
        minLength = 2,
        maxLength = 40,
        whitelist = [],
        tagClass = 'tagRs'
    } = options;
    log03('inicio TagEnTexto');

    const container = document.getElementById(containerId);
    const hiddenTagsInput = document.getElementById('postTagsHidden');
    const hiddenContentTextarea = document.getElementById('postContent');

    if (!container || !hiddenTagsInput || !hiddenContentTextarea) {
        return;
    }

    let tags = [];
    let normalText = "";

    container.addEventListener('input', handleInput);
    container.addEventListener('keydown', handleKeyDown);

    function handleInput(event) {
        processTags();
    }

    function handleKeyDown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            processTags();
        }
    }

    function processTags() {
        const content = container.innerText;
        const words = content.split(/(\s+)/);
        
        tags = [];
        normalText = "";
        container.innerHTML = '';
    
        words.forEach(word => {
            if (word.startsWith('#') && word.length > 1) {
                const tag = word.slice(1); // Remove the '#'
                if (isValidTag(tag) && !tags.includes(tag)) {
                    tags.push(tag);
                    const tagSpan = document.createElement('span');
                    tagSpan.className = tagClass;
                    tagSpan.textContent = word;
                    container.appendChild(tagSpan);
                } else {
                    normalText += word;
                    container.appendChild(document.createTextNode(word));
                }
            } else {
                normalText += word;
                container.appendChild(document.createTextNode(word));
            }
        });
    
        normalText = normalText.trim();
    
        updateHiddenInputs();
    
        // Set cursor position at the end
        const selection = window.getSelection();
        const range = document.createRange();
        
        if (container.childNodes.length > 0) {
            range.selectNodeContents(container);
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
        }
    
        // Mostrar tags procesados y texto normal en la consola
        log03('Tags procesados:', tags);
        log03('Texto normal:', normalText);
    }

    function updateHiddenInputs() {
        hiddenTagsInput.value = tags.join(',');
        hiddenContentTextarea.value = normalText;
    }

    function isValidTag(tag) {
        return tag.length >= minLength && tag.length <= maxLength && tags.length < maxTags && (whitelist.length === 0 || whitelist.includes(tag));
    }

    // Función para obtener los tags y el texto normal
    function getContent() {
        return { tags, normalText };
    }

    // Retorna la función getContent para que pueda ser utilizada externamente
    return getContent;
}

// Función para procesar tags al enviar el formulario
function procesarTagsSiExistenRs() {
    var tagsElement = document.getElementById('textoRs');
    var hiddenInput = document.getElementById('postTagsHidden');
    var contentTextarea = document.getElementById('postContent');

    if (tagsElement && hiddenInput && contentTextarea) {
        var content = tagsElement.innerText;
        var words = content.split(/\s+/);
        var tags = [];
        var fullText = "";

        words.forEach(word => {
            if (word.startsWith('#') && word.length > 1) {
                tags.push(word.slice(1));
            }
            fullText += word + " ";
        });

        hiddenInput.value = tags.join(',');
        contentTextarea.value = fullText.trim();
        log03('Tags procesados:', hiddenInput.value);
        log03('Contenido completo:', contentTextarea.value);
    }
}



