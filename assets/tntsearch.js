function TntSearch(){
  this.search_input = document.querySelector('.tntsearch-field');
  this.container = document.querySelector('.tntsearch-results');
  this.form = document.querySelector('.tntsearch-form');

  this.data = JSON.parse(this.search_input.dataset.tntsearch);

  this.snippet = this.data.snippet || '';
  this.limit = this.data.limit || '';
  this.uri = this.data.uri;
  
  this.timeout = '';
  
  if(this.search_input){
    var _this = this;
    this.search_input.addEventListener('keyup', function(){
  
    //delayed timer on this keyup event so it doesn't query the server a bunch of times unnecessarily 
    clearTimeout(this.timeout);
    this.timeout = setTimeout( function(){ _this.search() }, 400);
    })
  }
};


TntSearch.prototype.search = function(){
    var value = this.search_input.value;
    
    if (!value.length) {
      this.container.style.display = 'none';
      return false;
    }
    
    //start searching after 3 letters
  if (value.length < 3) { 
      return false; 
    }
    
    // stop form from submitting
    this.form.addEventListener('submit', function(event){ 
      event.preventDefault; 
      return false 
    });
    
    // build query
    var query = '?q=' + encodeURIComponent(value).replace(/%20/g,'+');
    query += "&ajax=true&l="+this.limit+"&sl="+this.snippet
    
    var _this = this;
    this.tntQuery(this.uri + query, function(data){
    _this.container.style.display = 'block';
    _this.container.innerHTML = data;
  });
  
}


TntSearch.prototype.tntQuery = function(url, success) {
  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  xhr.open('GET', url);
  xhr.onreadystatechange = function() {
    if (xhr.readyState>3 && xhr.status==200) success(xhr.responseText);
  };
  
  xhr.setRequestHeader('Accept', 'application/json');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send();
  return xhr;
}

  
function docReady(){
  var search = new TntSearch();
}

if (document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)){
  docReady();
} 
else {
  document.addEventListener('DOMContentLoaded', docReady);
}
