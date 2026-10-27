// polyfills
import 'babel-polyfill';

import domready from 'domready';
import search from './search';

const GravTNTSearch = () => {
    /* const uri = new URI(global.location.href, true);
    history.replace({
        search: global.location.search,
        hash: global.location.hash,
        state: {
            historyValue: uri.query.q || '',
            type: 'tntsearch',
        },
    });*/

    const searchForms = document.querySelectorAll('form.tntsearch-form');
    [...searchForms].forEach((form) => {
        const input = form.querySelector('.tntsearch-field');
        const clear = form.querySelector('.tntsearch-clear');
        const results = form.querySelector('.tntsearch-results');
        if (!input || !results) { return false; }

        let options = {};
        try {
            options = JSON.parse(input.dataset.tntsearch || '{}');
        } catch (e) {
            options = {};
        }

        form.addEventListener('submit', (event) => {
            // with `submit_to_search_page`, Enter and the search button go to the full results page
            if (options.submit && input.value.trim()) {
                search.cancel();
                return;
            }

            event.preventDefault();
        });
        input.addEventListener('focus', () => search(input, results));
        input.addEventListener('input', () => {
            if (clear) {
                clear.style.display = '';
            }
            search.cancel();
            search({ input, results });
        });

        if (clear) {
            clear.addEventListener('click', () => {
                if (clear) {
                    clear.style.display = 'none';
                }
                input.value = '';
                search.cancel();
                search({ input, results });
            });
        }

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
};

domready(GravTNTSearch);

window.GravTNTSearch = GravTNTSearch;
