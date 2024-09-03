function busqueda() {

    const searchForm = document.getElementById('search-form');

    if (searchForm) {
      searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const searchTerm = document.getElementById('dynamic-placeholder').value;
        window.location.search = `?search=${searchTerm}`; // Actualiza la URL con el término de búsqueda
      });
    }
    /*
    const tags = document.querySelectorAll('a[href*="/tag/"]');
    tags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            e.preventDefault();
            const urlParts = this.href.split('/');
            const tagText = urlParts[urlParts.length - 2];
            const searchUrl = 'https://2upra.com/samples/?search=' + encodeURIComponent(tagText);
            window.location.href = searchUrl;
        });
    }); 

    var phrases = ["Rock", "Guitar", "Phonk", "Vitagen", "Drum", "Chill", "Vocal", "Memphis", "Kick"];
    var txt = document.getElementById("dynamic-placeholder");
    
    // Verifica si el elemento existe antes de continuar
    if (txt) {
        var currentPhrase = 0;
        var currentLetter = 0;
        var typeSpeed = 60, backSpeed = 60, backDelay = 600;
        var typing = true;

        function getRandomPhraseIndex() {
            // Genera un índice aleatorio para seleccionar un elemento de phrases
            return Math.floor(Math.random() * phrases.length);
        }

        function type() {
            if (typing) {
                if (currentLetter < phrases[currentPhrase].length) {
                    txt.placeholder += phrases[currentPhrase].charAt(currentLetter);
                    currentLetter++;
                    setTimeout(type, typeSpeed);
                } else {
                    typing = false;
                    setTimeout(type, backDelay);
                }
            } else {
                if (currentLetter > 0) {
                    txt.placeholder = txt.placeholder.substring(0, txt.placeholder.length - 1);
                    currentLetter--;
                    setTimeout(type, backSpeed);
                } else {
                    typing = true;
                    // Selecciona un nuevo índice de frase al azar
                    currentPhrase = getRandomPhraseIndex();
                    setTimeout(type, typeSpeed);
                }
            }
        }

        type();
    }
    */
}
