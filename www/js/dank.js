var max_file_bytes;

window.onload = init_dank;

// start up the dankness
function init_dank() {
	console.log('initializing dank systems...');
	// handle nsfw toggle clicks
	var nsfw_toggle = document.getElementById('nsfw-hide-toggle');
	if (nsfw_toggle != undefined) {
		nsfw_toggle.addEventListener('click', nsfw_toggle_click);
	}
	// Check for the various File API support.
	if (document.getElementById('new-post-form') != undefined) {
		max_file_bytes = document.getElementById('max-file-bytes').value * 1;
		var dropzone = document.getElementById('file-drop-zone');
		if (window.File && window.FileReader && window.FileList && window.Blob) {
			// supported!
			document.getElementById('files').addEventListener('change', handleFileSelect, false);
			// Setup the dnd listeners.
			dropzone.addEventListener('dragover', handleDragOver, false);
			dropzone.addEventListener('drop', handleFileDrop, false);
		} else {
			// nevermind -- get rid of it
			dropzone.parentNode.removeChild(dropzone);
		}
	}
	// make videos easier to use
	var vids = document.getElementsByTagName('video');
	for (var i = 0; i < vids.length; i++) {
		//console.log(vids[i]);
		vids[i].addEventListener('click', video_click);
		vids[i].addEventListener('dblclick', video_doubleclick);
		//vids[i].addEventListener('ended', video_done);
		vids[i].volume = 1;
	}
	// handle comments
	var comment_btns = document.getElementsByClassName('post-comment-btn');
	if (comment_btns != undefined && comment_btns.length > 0) {
		for (var i = 0; i < comment_btns.length; i++) {
			comment_btns[i].addEventListener('click', comment_btn_click);
		}
	}
}

function handleFiles(files) {
	// files is a FileList of File objects. List some properties.
	var output = [];
	var total_bytes = 0;
	for (var i = 0, f; f = files[i]; i++) {
		total_bytes += f.size;
		output.push('<strong>', escape(f.name), '</strong> (', 
			f.type || 'n/a', ') - ',
			f.size, ' bytes;',
		'');
		
		// Only process image files.
		if (!f.type.match('image.*')) {
			continue;
		}
		
		var reader = new FileReader();
		
		// Closure to capture the file information.
		reader.onload = (function(theFile) {
			return function(e) {
				// Render thumbnail.
				var span = document.createElement('span');
				span.innerHTML = ['<img class="thumb" src="', e.target.result, '" title="', escape(theFile.name), '"/>'].join('');
				document.getElementById('file-list').insertBefore(span, null);
			};
		})(f);
		
		// Read in the image file as a data URL.
		reader.readAsDataURL(f);
	}
	document.getElementById('file-list').innerHTML = output.join('');
	if (total_bytes > max_file_bytes) {
		alert('warning: you\'re uploading files that are over the max upload limit');
	}
}

function handleFileDrop(e) {
	e.stopPropagation();
    e.preventDefault();
    var files = e.dataTransfer.files; // FileList object.
    document.getElementById('files').files = files;
    //handleFiles(files);
}

function handleFileSelect(e) {
	var files = e.target.files; // FileList object
	handleFiles(files);
}
	
function handleDragOver(e) {
	e.stopPropagation();
	e.preventDefault();
	e.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
}

function video_done(e) {
	this.currentTime = 0;
}

function video_click(e) {
	//console.log(e);
	//console.log(this);
	if (this.paused == true) {
		this.play();
	} else {
		this.pause();
	}
}

function video_doubleclick(e) {
	e.preventDefault();
	this.currentTime = 0;
	this.play();
}

function nsfw_toggle_click(e) {
	//console.log(e.target.checked);
	if (e.target.checked) {
		// set hide_dank_nsfw cookie to 1
		var now = new Date();
		var expire_date = now.setDate(now.getDate() + 30);
		docCookies.setItem('hide_dank_nsfw', '1', expire_date, '/', 'dankest.website');
	} else {
		// delete the hide_dank_nsfw cookie
		docCookies.removeItem('hide_dank_nsfw', '/', 'dankest.website');
	}
	window.location = './';
}

function comment_btn_click(e) {
	//console.log(e);
	var comment_form_children = e.target.parentNode.children;
	//console.log(comment_form_children);
	var post_id = comment_form_children[0].value * 1;
	var comment_text = comment_form_children[1].value;
	comment_form_children[2].value = 'Posting...';
	comment_form_children[2].disabled = true;
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				console.log('comment posted');
				var comment_lists = document.getElementsByClassName('comments-list');
				for (var i = 0; i < comment_lists.length; i++) {
					if (comment_lists[i].getAttribute('data-post-id') * 1 == post_id) {
						comment_lists[i].innerHTML += xmlhttp.responseText;
					}
				}
				comment_form_children[1].value = '';
				comment_form_children[2].value = 'Post »';
				comment_form_children[2].disabled = false;
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to post the comment: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to post the comment: ' + xmlhttp.responseText);
			} else {
				console.error('something else other than 200 was returned: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/comment/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=n&post_id=" + post_id + "&comment=" + encodeURIComponent(comment_text));
}

// cookie handling
// thx https://developer.mozilla.org/en-US/docs/Web/API/document.cookie
var docCookies = {
  getItem: function (sKey) {
    if (!sKey) { return null; }
    return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
  },
  setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
    var sExpires = "";
    if (vEnd) {
      switch (vEnd.constructor) {
        case Number:
          sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
          break;
        case String:
          sExpires = "; expires=" + vEnd;
          break;
        case Date:
          sExpires = "; expires=" + vEnd.toUTCString();
          break;
      }
    }
    document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
    return true;
  },
  removeItem: function (sKey, sPath, sDomain) {
    if (!this.hasItem(sKey)) { return false; }
    document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
    return true;
  },
  hasItem: function (sKey) {
    if (!sKey) { return false; }
    return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
  },
  keys: function () {
    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
    for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
    return aKeys;
  }
};