<?php
    $uploader = new Uploader();

    if (!empty($_FILES)) {
        $uploader->run();
    }
    elseif (!empty($_POST['tmpName']) && !empty($_POST['fileName'])) {
        $uploader->save();
    }

    /**
     * Class Uploader
     *
     * Upload files without limit of size
     *
     */
    class Uploader
    {
        /**
         * @var string contain temporary name of file which already uploading
         */
        protected $tmpName;

        /**
         * @var string path to root directory of site
         */
        protected $rootDir;

        /**
         * @var string dir that contain temporary files which uploading
         */
        protected $tmpLocation;

        /**
         * @var string dir to uploaded files
         */
        protected $saveDir;

        /**
         * Uploader constructor.
         *
         * Set all configuration path and files name
         */
        public function __construct()
        {
            $this->rootDir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
            $this->tmpLocation = $this->rootDir . 'tmp' . DIRECTORY_SEPARATOR;
            $this->saveDir = $this->rootDir . 'upload' . DIRECTORY_SEPARATOR;
            $this->tmpName = isset($_POST['tmpName']) ? $_POST['tmpName'] : '';
        }

        /**
         * Run or continue uploading.
         * Give response by json string
         */
        public function run()
        {
            $inputName = key($_FILES);
            $filename = $_FILES[$inputName]['name'];
            $tmpPath = $_FILES[$inputName]['tmp_name'];

            // if temporary file exists - adding data on it. If not exists creating it.
            if ($this->tmpName) {
                try {
                    $this->checkFile($this->tmpLocation . $this->tmpName);

                    $handle = fopen($this->tmpLocation . $this->tmpName, 'a');
                    fwrite($handle, file_get_contents($tmpPath));
                    fclose($handle);
                } catch (Exception $e) {
                    $this->generateResponse(['success' => false, 'error' => $e->getMessage()]);
                }
            }
            else {
                $this->tmpName = md5($filename . time());
                move_uploaded_file($tmpPath, $this->tmpLocation . $this->tmpName);
            }

            $this->generateResponse(['success' => true, 'tmpName' => $this->tmpName]);
        }

        /**
         * Validate uploaded file and move it to destination
         */
        public function save()
        {
            $filename =  html_entity_decode($_POST['fileName'], ENT_QUOTES, 'UTF-8'); // Sanitize the filename
            $pathToSave = $this->saveDir . $filename;
            $locationPath = $this->tmpLocation . $this->tmpName;

            // if file with the same name exists - generate new file name
            if (file_exists($pathToSave)) {
                $fileData = pathinfo($pathToSave);
                $pathToSave = $this->saveDir . $fileData['filename'] . '_' . time() . '.' . $fileData['extension'];
            }

            try {
                $this->validateFile($locationPath);

                if (rename($locationPath, $pathToSave)) {
                    $response = [
                        'success' => true,
                        'massage' => 'File uploaded successfully'
                    ];
                }
                else {
                    $response = [
                        'success' => false,
                        'massage' => 'There was a problem with uploading'
                    ];
                    @unlink($locationPath);
                }
                $this->generateResponse($response);
            } catch (Exception $e) {
                @unlink($locationPath);
                $this->generateResponse(['success' => false, 'error' => $e->getMessage()]);
            }
        }

        /**
         * @param $path string path to temporary file
         *
         * @throws \Exception if file not exists or not available for writing
         */
        protected function checkFile($path)
        {
            // checking if file exists
            if (!file_exists($path)) {
                throw new Exception('Can not found temp file');
            }

            //checking if file is allowed to write
            if (!is_writable($path)) {
                throw new Exception('File is not writable');
            }

        }

        /**
         * @param $filePath string path to temporary file
         *
         * @throws \Exception if file not valid
         */
        protected function validateFile($filePath)
        {
            // Check to see if any PHP files are trying to be uploaded
            if (preg_match('/\<\?php/i', file_get_contents($filePath))) throw new Exception('File contain not allowed content');

            // there might be another rules to validate file (depend on task)
        }

        /**
         * @param $responseArray array contain status and params for response
         */
        protected function generateResponse($responseArray)
        {
            die(json_encode($responseArray));
        }

    }