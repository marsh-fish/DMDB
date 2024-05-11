<script>
    function CustomAlert() {
        this.alert = function(message, title, callback) {
            document.body.innerHTML = document.body.innerHTML + '<div id="dialogoverlay"></div><div id="dialogbox" class="slit-in-vertical"><div><div id="dialogboxhead"></div><div id="dialogboxbody"></div><div id="dialogboxfoot"></div></div></div>';

            let dialogoverlay = document.getElementById('dialogoverlay');
            let dialogbox = document.getElementById('dialogbox');

            let winH = window.innerHeight;
            dialogoverlay.style.height = winH + "px";

            dialogbox.style.top = "100px";

            dialogoverlay.style.display = "block";
            dialogbox.style.display = "block";

            document.getElementById('dialogboxhead').style.display = 'block';

            if (!title) {
                document.getElementById('dialogboxhead').style.display = 'none';
            } else {
                document.getElementById('dialogboxhead').innerHTML = title;
            }
            if (!message) {
                document.getElementById('dialogboxbody').style.display = 'none';
                document.getElementById('dialogboxfoot').innerHTML = 
                '<button class="pure-material-button-contained active" onclick="customAlert.ok_sure(' + (callback ? callback : '') + 
                ')">OK</button><button class="pure-material-button-contained active" onclick="customAlert.cancel()">Cancel</button>';
            } else {
                document.getElementById('dialogboxbody').innerHTML = '<p>' + message + '</p><input type="text" id="textInput">';
                document.getElementById('dialogboxfoot').innerHTML = 
                '<button class="pure-material-button-contained active" onclick="customAlert.ok(' + (callback ? callback : '') + 
                ')">OK</button><button class="pure-material-button-contained active" onclick="customAlert.cancel()">Cancel</button>';
            }
        }

        this.ok = function(callback) {
            var inputText = document.getElementById('textInput').value;
            console.log("Input text:", inputText);

            // Create a new XMLHttpRequest object
            var xhr = new XMLHttpRequest();

            // Configure the request
            xhr.open('POST', 'upload.php', true);

            // Set up the callback function for when the request completes
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Request was successful
                    console.log('Upload successful');
                    // Reload the current page
                    location.reload();
                } else {
                    // Request failed
                    console.error('Upload failed');
                    // Handle the error gracefully
                }
            };

            // Set up the callback function for if there's an error with the request
            xhr.onerror = function() {
                console.error('Request failed');
                // Handle the error gracefully
            };

            // Set the data to be sent in the request body
            var formData = new FormData();
            formData.append('inputData', inputText);

            // Send the request with the input data
            xhr.send(formData);

            // Hide the dialog box and overlay
            document.getElementById('dialogbox').style.display = "none";
            document.getElementById('dialogoverlay').style.display = "none";

            // Execute the callback if provided
            if (callback) callback(inputText);
        }
        
        this.ok_sure = function(categoryId, callback) {
            // Create a new XMLHttpRequest object
            var xhr = new XMLHttpRequest();

            // Configure the request
            xhr.open('POST', 'category.php', true);

            // Set up the callback function for when the request completes
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Request was successful
                    console.log('Category deletion successful');
                    // Reload the current page
                    location.reload();
                } else {
                    // Request failed
                    console.error('Category deletion failed');
                    // Handle the error gracefully
                }
            };

            // Set up the callback function for if there's an error with the request
            xhr.onerror = function() {
                console.error('Request failed');
                // Handle the error gracefully
            };

            // Set the data to be sent in the request body
            var formData = new FormData();
            formData.append('delete_category_id', categoryId);

            // Send the request with the input data
            xhr.send(formData);

            document.getElementById('dialogbox').style.display = "none";
            document.getElementById('dialogoverlay').style.display = "none";

            // Execute the callback if provided
            if (callback) callback(categoryId);
        }

        this.cancel = function(callback) {
            document.getElementById('dialogbox').style.display = "none";
            document.getElementById('dialogoverlay').style.display = "none";
        }
    }

    let customAlert = new CustomAlert();

</script>