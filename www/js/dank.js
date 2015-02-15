var max_file_bytes;

window.onload = init_dank;

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