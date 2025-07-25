function processImage(thisImage) {
  //var thisImage = document.getElementById(id);
  if (thisImage.complete && thisImage.naturalHeight == 1 && thisImage.naturalWidth == 1) {
    //thisImage.onload = "return;";
    thisImage.src = "items/default.png";
    thisImage.style.maxHeight = "250px";
  }
}

function checkImages() {
  var allImages = document.getElementsByTagName('img');
  for(var i = 0; i < allImages.length ; i++) {
    processImage(allImages[i]);
  }
}

function rotateImages() {
  if (location.search.length != 0) {
    clearInterval(rotateInterval);
    return;
  }
  var allImages = document.querySelectorAll('.book-list--item--cover img');
  var i = Math.floor(Math.random() * allImages.length);
  //console.log('rotating image ' + i);
  allImages[i].src = 'images/' + allImages[i].dataset.image + Math.ceil(Math.random() * 5) + '.jpg';
}

function reset() {
  clearTimeout(timeout);
  console.log("Interval reset!");
  timeout = setTimeout(function(){
    window.location.replace(window.location.origin + window.location.pathname);
  } , 180000);
}

  function createDescription(el) {
        console.log(el.dataset.title);
        console.log(el.dataset.description);
        console.log(el.dataset.url);
        console.log(el.dataset.cover);
        console.log(el.dataset.id);
        console.log(el.dataset.call);
        console.log(el.dataset.json);
        var title = el.dataset.title;
        var summary = el.dataset.description;
        var url = el.dataset.url;
        var cover = el.dataset.cover;
        var id = el.dataset.id;
        var call = el.dataset.call;
        if(window.top!=window.self) {
	  window.open(url, "_blank");
          return;
        }
        if (summary.length > 420 ) {
            summary = summary.substring(0,420) + "...";
        }
        var desc = "<center><br><br><h1>" + title + "</h1>";
        var unavailable = el.querySelector('img').classList.contains("unavailable");
        desc += "<img ";
        if(unavailable) {
          desc += "class='unavailable' ";
        } 
        desc += "src='" + cover + "'>" + "</center>";
        desc += "<p>" + summary + "</p><center><button class='hold' data-call='" + call.replace('\'','') + "' data-item='" + id + "' onclick=\"placeHold(this)\">HOLD</button></center>";
        var htm = document.createElement("div");
        htm.setAttribute('id', 'eventDescription');
	htm.setAttribute('class', id);
        htm.innerHTML = desc;
        openModal(htm);
        document.getElementById("eventDescription").scrollTop = 0;
        reset();
  }

function placeHold(item) {
  console.log('Hold button clicked');
  brompt({
    /* prompt title */
    title: 'Please enter your Library card number',

    /*
   * prompt design type - 
   * success, info, error or warning
   */
    type: 'info',
    
    /* OK button custom done */
    okButtonText: 'Done',

    /* Cancel button custom text */
    cancelButtonText: 'Back',

    /* same function as that of blurt() */
    escapable: false,

    /*
   * success callback function
   * this callback is passed the value which the user has entered
   */
    onConfirm: function(val){
        if(typeof val == 'string' && val.length == 0) {
          return;
        }
        placeHoldCallback(val);
    },
    
    /*
   * method to be called
   * when user presses cancel button
   */
    onCancel: function(){
        return;
        //blurt('Error', 'You cancelled the operation.', 'error');
    }
  });
}

function placeHoldCallback(card) {
  var item = document.querySelector(".hold");
  item = item.dataset.item;
  if (card == null) {
    //alert('Hold canceled!');
    blurt('Canceled','Hold canceled!','error');
  } else {
    var base = window.location.origin + window.location.pathname;
    var url = base + "";
    var body = "hold=" + item + "&card=" + card;
    console.log("loading: " + url);
      fetch(url, {
        method: "POST",
         mode: "cors",
         headers: {
	    'Content-Type': 'application/x-www-form-urlencoded',
          },
         body: body
      })
        .then(response => response.text())
        .then(text => holdResult(text))
        .catch(error => {
          holdResult('Please see staff for help');
        });
  }
}

function holdResult(result) {
  var el = document.querySelector(".hold");
  var call = el.dataset.call;
  if (isNaN(result)) {
    //alert(result);
    blurt('Error', result,'error');
  } else {
    var unavailable = document.querySelector("#eventDescription img").classList.contains("unavailable");
    if (unavailable) {
      blurt('Hold placed!', 'We will notify you when it becomes available.','warning');
    } else if (call.startsWith("J GAME") || call.startsWith("J TOY") || call.startsWith("J PUZZLE")) {
      blurt('Hold placed!', 'We will notify you when it becomes available.','success');
    }  else if (call.startsWith("J ")) {
      blurt('Hold placed!', 'You can request your item at the YOUTH SERVICES desk.','success');
    } else if (call.startsWith("GAME ")) {
      blurt('Hold placed!', 'You can request your item at the second floor PUBLIC SERVICE desk.','success');
    } else {
      blurt('Hold placed!', 'You can request your item at the CIRCULATION desk.','success');
    }
  }
}

  function openModal(data) {
    var el = document.getElementById('communicoWrapper');
    el.innerHTML = "";
    el.appendChild(data);
    modal.style.display = "block";
    el.focus();
  }

  function closeModal() {
    modal.style.display = "none";
  }

var timeout = "";
var modal = document.getElementById('communicoModal');
var span = document.getElementById("closeModal");
if (span) {
  span.onclick = function(event) {
    closeModal();
  };
}
window.onclick = function(event) {
  if (event.target == modal) {
    closeModal();
  }
  reset();
};

window.onscroll = function(){
  reset();
};

window.onkeydown = function(){
  reset();
};

window.onkeyup = function(){
  reset();
};


var imageInterval = setInterval(checkImages, 1000);

setTimeout(function() { clearInterval(imageInterval) }, 5000);

var rotateInterval = setInterval(rotateImages, 2000);
