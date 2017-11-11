// polyfills
import 'babel-polyfill';

import domready from 'domready';
import search from './search';

domready(() => {
    const searchForms = document.querySelectorAll('form.tntsearch-form');

    [...searchForms].forEach((form) => {
        const input = form.querySelector('.tntsearch-field');
        const results = form.querySelector('.tntsearch-results');
        if (!input || !results) { return false; }

        form.addEventListener('submit', (event) => event.preventDefault());
        input.addEventListener('focus', () => search(input, results));
        input.addEventListener('input', () => {
            search.cancel();
            search(input, results);
        });

        return this;
    });

    document.addEventListener('click', (event) => {
        [...searchForms].forEach((form) => {
            if (!form.querySelector('.tntsearch-dropdown')) { return; }
            if (!form.contains(event.target)) {
                form.querySelector('.tntsearch-results').style.display = 'none';
            }
        });
    });
});
