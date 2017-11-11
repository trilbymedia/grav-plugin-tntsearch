import throttle from 'lodash/throttle';

export const DEFAULTS = {
    uri: '',
    limit: '',
    snippet: '',
    min: 3,
    in_page: false,
};


export default throttle(async (input, results) => {
    if (!input || !results) { return false; }

    const value = input.value.trim();
    const data = Object.assign({}, DEFAULTS, JSON.parse(input.dataset.tntsearch || '{}'));

    if (!value) {
        results.style.display = 'none';
        return false;
    }

    if (value.length < data.min) {
        return false;
    }

    const params = {
        q: value,
        l: data.limit,
        sl: data.snippet,
        ajax: true,
    };

    const query = Object.keys(params)
        .map(k => `${encodeURIComponent(k)}=${encodeURIComponent(params[k])}`)
        .join('&')
        .replace(/%20/g, '+');

    fetch(`${data.uri}?${query}`)
        .then((response) => response.text())
        .then((response) => {
            console.log(data.in_page);
            return response;
        })
        .then((response) => {
            results.style.display = '';
            results.innerHTML = response;

            return response;
        });

    return this;
}, 350, { leading: false });
