exports.tntsearch = class{
  constructor(){
    this.search = document.querySelectorAll('.tntsearch-field');
    this.container = document.querySelector('.tntsearch-results');
    this.form = this.search.closest('form');

    this.timeout = '';
    
    this.init();
  }

  init(){
    if(this.search){
      this.search.addEventListener('keyup', () => {

        //delayed timer on this keyup event so it doesn't query the server a bunch of times unnecessarily 
        clearTimeout(this.timeout);
        this.timeout = setTimeout( () => this.search(), 400);
      })
    }
  }

  search(){
    let value = this.search.value;
    let data = this.search.dataset.tntsearch

    // console.log(value);
    if (!value.length) {
      this.container.style.display = 'none';
      return false;
    }

    //start searching after 3 letters
    if (value.length < 3) { 
      return false; 
    }

    let snippet = data.snippet || '';
    let limit = data.limit || '';
    let uri = data.uri;

    // stop form from submitting
    form.addEventListener('submit', (event) => { 
      event.preventDefault; 
      return false 
    });

    // build query
    let query = '?q=' + encodeURIComponent(value).replace(/%20/g,'+');
    query += "&ajax=true&l="+limit+"&sl="+snippet

    //using form action as uri
    fetch(uri + query, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      }
    })
    .then(function (response){
      return response.text();
    })
    .then(function(data){
      container.style.display = 'block';
      container.innerHTML = data;
    });

  }

}
