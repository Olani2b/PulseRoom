
    function scrollToSection(direction) {
        const sections = document.querySelectorAll('header, .w3-container, footer');
        let currentSectionIndex = Array.from(sections).findIndex(section => {
            return section.getBoundingClientRect().top >= 0;
        });

        if (direction === 'down') {
            // Scrolling down
            if (currentSectionIndex < sections.length - 1) {
                sections[currentSectionIndex + 1].scrollIntoView({ behavior: 'smooth' });
            }
        } else if (direction === 'up') {
            // Scrolling up
            if (currentSectionIndex > 0) {
                sections[currentSectionIndex - 1].scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    // Aggiungi event listener per il tasto freccia in basso
    document.addEventListener('DOMContentLoaded', function() {
        // Aggiungi event listener per tutte le icone di scroll
        const scrollArrows = document.querySelectorAll('.scroll-arrows .fa-arrow-down');
        scrollArrows.forEach(function(scrollArrow) {
            scrollArrow.addEventListener('click', function() {
                scrollToSection('down');
            });
        });
    });

    // Aggiungi event listener per il tasto freccia in alto
    document.addEventListener('DOMContentLoaded', function() {
        // Aggiungi event listener per tutte le icone di scroll
        const scrollArrows = document.querySelectorAll('.scroll-arrows .fa-arrow-up');
        scrollArrows.forEach(function(scrollArrow) {
            scrollArrow.addEventListener('click', function() {
                scrollToSection('up');
            });
        });
    });
    
