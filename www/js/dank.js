var max_file_bytes;

window.onload = init_dank;

function init_dank() {
	console.log('initializing dank systems...');
	// Check for the various File API support.
	if (document.getElementById('new-post-form') != undefined) {
		max_file_bytes = document.getElementById('max-file-bytes').value * 1;
		if (window.File && window.FileReader && window.FileList && window.Blob) {
			// supported!
			document.getElementById('files').addEventListener('change', handleFileSelect, false);
			// Setup the dnd listeners.
			var dropZone = document.getElementById('file-drop-zone');
			dropZone.addEventListener('dragover', handleDragOver, false);
			dropZone.addEventListener('drop', handleFileDrop, false);
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