// polyfills
import 'babel-polyfill';

import domready from 'domready';
import URI from 'url-parse';
import search from './search';
import history from './history';

domready(() => {
    const uri = new URI(global.location.href, true);
    history.replace({
        search: global.location.search,
        hash: global.location.hash,
        state: {
            historyValue: uri.query.q || '',
            type: 'tntsearch',
        },
    });

    const searchForms = document.querySelectorAll('form.tntsearch-form');
    [...searchForms].forEach((form) => {
        const input = form.querySelector('.tntsearch-field');
        const clear = form.querySelector('.tntsearch-clear');
        const results = form.querySelector('.tntsearch-results');
        if (!input || !results) { return false; }

        form.addEventListener('submit', (event) => event.preventDefault());
        input.addEventListener('focus', () => search(input, results));
        input.addEventListener('input', () => {
            clear.style.display = '';
            search.cancel();
            search({ input, results });
        });

        if (clear) {
            clear.addEventListener('click', () => {
                input.value = '';
                clear.style.display = 'none';
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
});
