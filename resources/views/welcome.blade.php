<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>CSV Processor</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        .upload-form {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }

        .upload-form:hover {
            border-color: #aaa;
        }

        .upload-form.dragging {
            background-color: #e9ecef;
            border-color: #6c757d;
        }

        .upload-list {
            margin-top: 30px;
        }

        .upload-item {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .upload-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            transition: width 0.3s;
        }

        .status-pending {
            color: #6c757d;
        }

        .status-processing {
            color: #0d6efd;
        }

        .status-completed {
            color: #198754;
        }

        .status-failed {
            color: #dc3545;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>


</head>

<body>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>CSV File Processor</h4>
                </div>
                <div class="card-body">
                    <div class="upload-form" id="uploadForm">
                        <h5>Upload CSV File</h5>
                        <input type="file" id="fileInput" class="d-none" accept=".csv">
                        <button id="selectFileBtn" class="btn btn-primary">Select File</button>
                        <div id="fileInfo" class="mt-3 d-none">
                            <div>Selected file: <span id="fileName"></span></div>
                            <button id="uploadBtn" class="btn btn-success btn-sm mt-2">Upload File</button>
                            <button id="removeBtn" class="btn btn-danger btn-sm mt-2">Remove File</button>
                        </div>
                    </div>
                    <div class="upload-list mt-4">
                        <h5>Upload History</h5>
                        <div id="uploadList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const fileInput = document.getElementById('fileInput');
        const selectFileBtn = document.getElementById('selectFileBtn');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const removeBtn = document.getElementById('removeBtn');
        const uploadBtn = document.getElementById('uploadBtn');

        selectFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                fileName.textContent = file.name;
                selectFileBtn.classList.add('d-none');
                fileInfo.classList.remove('d-none');
            } else {

            }
        });

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.value = '';
            fileName.textContent = undefined;
            fileInfo.classList.add('d-none');
            selectFileBtn.classList.remove('d-none');
        });

        uploadBtn.addEventListener('click', function() {
            uploadBtn.disabled = true;
            removeBtn.disabled = true;
            const file = fileInput.files[0];
            if (!file) {
                alert("Please select a file first.");
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            axios.post('/uploads', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    fileInfo.classList.add('d-none');
                    fileInput.value = '';
                    fetchUploads();
                })
                .catch(error => {
                    console.error('Upload error:', error);
                })
                .finally(() => {
                    uploadBtn.disabled = false;
                    removeBtn.disabled = false;
                    selectFileBtn.classList.remove('d-none');
                    fileInfo.classList.add('d-none');
                });
        });

        function fetchUploads() {
            axios.get('/uploads')
                .then(response => {
                    renderUploads(response?.data ?? {});
                })
                .catch(error => {
                    console.error('Error fetching uploads:', error);
                });
        }

        function renderUploads(uploads) {
            uploadList.innerHTML = '';
            if (uploads.length === 0) {
                uploadList.innerHTML = '<div class="text-muted">No uploads yet</div>';
                return;
            }

            uploads.forEach(upload => {
                const uploadItem = document.createElement('div');
                uploadItem.className = 'upload-item';

                let statusClass = '';
                switch (upload.status) {
                    case 'pending':
                        statusClass = 'status-pending';
                        break;
                    case 'processing':
                        statusClass = 'status-processing';
                        break;
                    case 'completed':
                        statusClass = 'status-completed';
                        break;
                    case 'failed':
                        statusClass = 'status-failed';
                        break;
                }

                let progressBar = '';

                uploadItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6>${upload.filename}</h6>
                            <div class="text-muted small">Uploaded on ${upload.created_at}</div>
                        </div>
                        <div>
                            <span class="badge rounded-pill ${statusClass}">${upload.status}</span>
                        </div>
                    </div>
                    ${progressBar}
                `;

                uploadList.appendChild(uploadItem);
            });
        }

        function setupPolling() {
            setInterval(fetchUploads, 3000);
        }
        setupPolling();
    </script>
</body>

</html>