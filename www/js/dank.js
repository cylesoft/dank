var max_file_bytes;
var current_max_page = 1;
var has_more_content = true;
var loading_stuff = false;

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
	
	max_scroll_limit = Math.max( document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight );
	
	if (document.getElementById('content-list-page') != undefined) {
		window.addEventListener('scroll', scroll_handler);
	}
	
	init_post_stuff();
	init_comment_stuff();
}

// handle all the event handlers relating to posts
function init_post_stuff() {
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
	
	// handle post approval
	var approve_btns = document.getElementsByClassName('approve-post');
	if (approve_btns != undefined && approve_btns.length > 0) {
		for (var i = 0; i < approve_btns.length; i++) {
			approve_btns[i].addEventListener('click', approve_btn_click);
		}
	}
	
	// handle post disapproval
	var disapprove_btns = document.getElementsByClassName('disapprove-post');
	if (disapprove_btns != undefined && disapprove_btns.length > 0) {
		for (var i = 0; i < disapprove_btns.length; i++) {
			disapprove_btns[i].addEventListener('click', disapprove_btn_click);
		}
	}
	
	// handle showing NSFW content anyway
	var nsfw_show_btns = document.getElementsByClassName('nsfw-show-anyway-btn');
	if (nsfw_show_btns != undefined && nsfw_show_btns.length > 0) {
		for (var i = 0; i < nsfw_show_btns.length; i++) {
			nsfw_show_btns[i].addEventListener('click', nsfw_show_click);
		}
	}
	
}

function init_comment_stuff() {
	// handle comment edit buttons
	var comment_edit_btns = document.getElementsByClassName('edit-comment');
	if (comment_edit_btns != undefined && comment_edit_btns.length > 0) {
		for (var i = 0; i < comment_edit_btns.length; i++) {
			comment_edit_btns[i].addEventListener('click', comment_edit_click);
		}
	}
	
	// handle comment delete buttons
	var comment_delete_btns = document.getElementsByClassName('delete-comment');
	if (comment_delete_btns != undefined && comment_delete_btns.length > 0) {
		for (var i = 0; i < comment_delete_btns.length; i++) {
			comment_delete_btns[i].addEventListener('click', comment_delete_click);
		}
	}
}

// handle scrolling
function scroll_handler(e) {
	if (loading_stuff) {
		return;
	}
	var current_scroll_length = Math.max(document.body.scrollTop, document.documentElement.scrollTop)  + window.innerHeight;
	var max_scroll_limit = Math.max( document.body.scrollHeight, document.body.offsetHeight, document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight );
	//console.log('scrolling... at: ' + current_scroll_length + ', max: ' + max_scroll_limit);
	if (current_scroll_length >= max_scroll_limit && has_more_content) {
		console.log('scrolled to the end of the page, loading more content!');
		load_more_content();
	}
}

// handle incoming files
function handleFiles(files) {
	// files is a FileList of File objects. List some properties.
	var output = []; // this'll hold our output html
	var total_bytes = 0; // track total size
	for (var i = 0, f; f = files[i]; i++) {
		total_bytes += f.size; // add to total size so far
		// show some file info
		output.push('<strong>', escape(f.name), '</strong> (', 
			f.type || 'n/a', ') - ',
			f.size, ' bytes;',
		'');
		// only process image files
		if (!f.type.match('image.*')) {
			continue;
		}
		// init a new file reader
		var reader = new FileReader();
		// closure to capture the file information
		reader.onload = (function(theFile) {
			return function(e) {
				// render thumbnail
				var span = document.createElement('span');
				span.innerHTML = ['<img class="thumb" src="', e.target.result, '" title="', escape(theFile.name), '"/>'].join('');
				document.getElementById('file-list').insertBefore(span, null);
			};
		})(f);
		// read in the image file as a data URL
		reader.readAsDataURL(f);
	}
	document.getElementById('file-list').innerHTML = output.join('');
	if (total_bytes > max_file_bytes) {
		alert('warning: you\'re uploading files that are over the max upload limit');
	}
}

// handle a file being dropped into the drop zone
function handleFileDrop(e) {
	e.stopPropagation();
    e.preventDefault();
    var files = e.dataTransfer.files; // FileList object.
    // give it to the file input instead of processing it yourself
    document.getElementById('files').files = files;
    //handleFiles(files);
}

// handle a file being selected with the input box
function handleFileSelect(e) {
	var files = e.target.files; // FileList object
	handleFiles(files);
}

// handle dragging a file over the dropzone
function handleDragOver(e) {
	e.stopPropagation();
	e.preventDefault();
	e.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
}

// when a video is done, rewind
function video_done(e) {
	this.currentTime = 0;
}

// clicking on a video once plays/pauses it
function video_click(e) {
	//console.log(e);
	//console.log(this);
	if (this.paused == true) {
		this.play();
	} else {
		this.pause();
	}
}

// double clicking on a video rewinds it and plays it again!
function video_doubleclick(e) {
	e.preventDefault();
	this.currentTime = 0;
	this.play();
}

// deal with somebody clicking the NSFW toggle
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

// deal with somebody making a comment
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
				console.error('Something else other than 200 was returned when posting the comment: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/comment/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=n&post_id=" + post_id + "&comment=" + encodeURIComponent(comment_text));
}

// deal with somebody clicking that approve button
function approve_btn_click(e) {
	//console.log(e);
	var post_id = e.target.getAttribute('data-post-id');
	console.log('approving post #'+post_id);
	e.target.setAttribute('value', 'approving...');
	e.target.disabled = true;
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				console.log('post approval accepted');
				if (xmlhttp.responseText == 'approved') {
					// content totally approved
					console.log('post is now approved');
					e.target.setAttribute('value', 'APPROVED.');
					e.target.parentNode.parentNode.className = e.target.parentNode.parentNode.className.replace('peer-approval', '');
					var unneeded_stuff = e.target.parentNode.parentNode.getElementsByClassName('peer-approval');
					for (var i = 0; i < unneeded_stuff.length; i++) {
						unneeded_stuff[i].parentNode.removeChild(unneeded_stuff[i]);
					}
				} else {
					// content still needs more votes
					console.log('post needs more votes');
					e.target.setAttribute('value', 'APPROVED. (Needs more votes to be public.)');
				}
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to approve the post: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to approve the post: ' + xmlhttp.responseText);
			} else {
				console.error('Something other than 200 was returned when approving the post: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/content/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=approve&post_id=" + post_id);
}

// deal with somebody clicking that disapprove button
function disapprove_btn_click(e) {
	//console.log(e);
	var post_id = e.target.getAttribute('data-post-id');
	console.log('disapproving post #'+post_id);
	e.target.setAttribute('value', 'disapproving...');
	e.target.disabled = true;
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				console.log('post disapproval accepted');
				if (xmlhttp.responseText == 'deleted') {
					// content deleted based on disapproval
					console.log('post is now deleted');
					var the_post = document.getElementById('post-' + post_id);
					the_post.parentNode.removeChild(the_post);
				} else {
					// content still needs more votes
					console.log('post needs more votes');
					e.target.setAttribute('value', 'LAME. (Needs more votes to be deleted.)');
				}
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to disapprove the post: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to disapprove the post: ' + xmlhttp.responseText);
			} else {
				console.error('Something other than 200 was returned when disapproving the post: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/content/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=disapprove&post_id=" + post_id);
}

// deal with clicking on one of the "show me this NSFW anyway..." buttons
function nsfw_show_click(e) {
	var post_id = e.target.getAttribute('data-post-id');
	console.log('showing nsfw post #'+post_id);
	var post = document.getElementById('post-'+post_id);
	// remove button
	e.target.parentNode.removeChild(e.target);
	// show content
	var post_content = post.getElementsByClassName('post-content');
	for (var i = 0; i < post_content.length; i++) {
		post_content[i].style.display = 'block';
	}
}

// deal with clicking on one of the edit comment buttons
function comment_edit_click(e) {
	var comment_id = e.target.getAttribute('data-comment-id');
	console.log('editing comment ID #'+comment_id);
	var comment = document.getElementById('comment-' + comment_id);
	var edit_comment_form = document.getElementById('edit-comment-'+comment_id);
	//console.log(comment);
	//comment.parentNode.removeChild(comment);
	//comment.style.display = 'none';
	edit_comment_form.style.display = 'block';
	comment.parentNode.replaceChild(edit_comment_form, comment);
	document.getElementById('save-edited-comment-'+comment_id).addEventListener('click', comment_edit_save_click);
}

// deal with saving the edited comment
function comment_edit_save_click(e) {
	var comment_id = e.target.getAttribute('data-comment-id');
	console.log('saving edit to comment ID #'+comment_id);
	var new_comment_text = document.getElementById('edited-comment-'+comment_id).value;
	//console.log('new comment: ' + new_comment_text);
	//var comment = document.getElementById('comment-' + comment_id);
	var edit_comment_form = document.getElementById('edit-comment-'+comment_id);
	//edit_comment_form.parentNode.removeChild(edit_comment_form);
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				console.log('comment edit saved');
				var dummy = document.createElement('div');
				dummy.innerHTML = xmlhttp.responseText;
				edit_comment_form.parentNode.replaceChild(dummy.firstChild, edit_comment_form);
				init_comment_stuff();
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to post the comment: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to post the comment: ' + xmlhttp.responseText);
			} else {
				console.error('Something else other than 200 was returned when posting the comment: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/comment/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=e&comment_id=" + comment_id + "&comment=" + encodeURIComponent(new_comment_text));
}

// deal with clicking on one of the delete comment buttons
function comment_delete_click(e) {
	if (confirm('Are you sure?') == false) {
		return;
	}
	var comment_id = e.target.getAttribute('data-comment-id');
	console.log('deleting comment ID #'+comment_id);
	var comment = document.getElementById('comment-' + comment_id);
	var edit_comment_form = document.getElementById('edit-comment-'+comment_id);
	e.target.setAttribute('value', 'deleting...');
	e.target.disabled = true;
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				console.log('comment deletion accepted');
				comment.parentNode.removeChild(comment);
				edit_comment_form.parentNode.removeChild(edit_comment_form);
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to delete the comment: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to delete the comment: ' + xmlhttp.responseText);
			} else {
				console.error('Something other than 200 was returned when deleting the comment: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/comment/process/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("a=d&comment_id=" + comment_id);
}

// load more content, for use with infinite scrolling
function load_more_content() {
	console.log('loading more content...');
	loading_stuff = true;
	document.getElementById('loading-indicator').style.display = 'block';
	current_max_page++; // load the next page
	var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				if (xmlhttp.responseText == '') {
					document.getElementById('loading-indicator').style.display = 'none';
					has_more_content = false;
					return;
				}
				console.log('got more content, yay!');
				document.getElementById('posts').innerHTML += xmlhttp.responseText;
				init_post_stuff();
				document.getElementById('loading-indicator').style.display = 'none';
				loading_stuff = false;
			} else if (xmlhttp.status == 400) {
				console.error('There was an error 400 when trying to disapprove the post: ' + xmlhttp.responseText);
			} else if (xmlhttp.status == 500) {
				console.error('There was an error 500 when trying to disapprove the post: ' + xmlhttp.responseText);
			} else {
				console.error('Something other than 200 was returned when disapproving the post: ' + xmlhttp.responseText);
			}
		}
	}
	xmlhttp.open("POST", "/content/fetch/", true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send("page="+current_max_page);
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