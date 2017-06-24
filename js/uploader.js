function Upload() {
    var self = this,
        file = null,
        uploadUrl = '/uploader.php',
        progressBar = $('.progress'),
        uploadFileSize = 1e6, // ~ 1Mb
        pieces = 0,
        currentPies = 0;

    // set selected file
    self.setFile = function (fileInput) {
        if (!fileInput.files.length) {
            self.errorHandler('Select file to upload, please.');
            return;
        }

        file = fileInput.files[0];
    };

    // count pieces of file that will be sending and start sending
    self.startUploading = function () {
        if (!file) {
            self.errorHandler('File not found');
            return;
        }

        progressBar.removeClass('hidden'); //display progress of uploading

        pieces = Math.ceil(file.size / uploadFileSize);

        self.startSending();
    };

    // return piece of file
    self.getPiece = function() {
        var start = currentPies * uploadFileSize;
        var end = Math.min((currentPies+1) * uploadFileSize, file.size);

        currentPies++;

        return file.slice(start, end);
    };

    // form data and send it by ajax
    self.startSending = function(tmpName) {
        var fd = new FormData(),
            name = 'file';

        fd.append(name, self.getPiece());

        if(tmpName) {
            fd.append('tmpName', tmpName);
        }

        $.ajax({
            url: uploadUrl,
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function(json){
                json = JSON.parse(json);
                if(typeof json.error == 'undefined'){
                    setTimeout(function () {
                        if(currentPies < pieces) {
                            self.startSending(json.tmpName);
                        }
                        else {
                            self.finishUpload(json.tmpName, file.name);
                        }
                        self.updateProgress();
                    }, 100);

                }
                else{
                    self.destroyProgress();
                    self.errorHandler(json.error);
                }
            }
        });
    };

    // send request that massage server about finish uploading
    self.finishUpload = function(tmpName, name) {
        $.ajax({
            url: uploadUrl,
            type: "POST",
            dataType: 'json',
            data:{tmpName : tmpName, fileName : name},
            success: function(json){
                if(typeof json.error == 'undefined'){
                    self.errorHandler(json.massage);
                }
                else{
                    self.errorHandler(json.error);
                }
            }
        }).always(function () {
            file = null;
            pieces = 0;
            currentPies = 0;
            $('input[type=file]').val('');
            self.destroyProgress();
        });
    };

    self.updateProgress = function (percent) {
        if(typeof percent === 'undefined') {
            percent = Math.floor(currentPies / pieces * 100)
        }

        progressBar.find('.progress-bar').attr('aria-valuenow', percent).css('width', percent + '%').text(percent + '%');
    };

    self.destroyProgress = function () {
        self.updateProgress(0);
        progressBar.addClass('hidden');
    };

    // hendle massage for user
    self.errorHandler = function (msg) {
        alert(msg);
    }
}