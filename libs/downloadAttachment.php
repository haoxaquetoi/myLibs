<?php

/**
 * Tải tập tin từ link url online
 */
class transferFile
{

    /**
     * Danh sách định dạng file hợp lệ
     * @var array
     */
    protected $arrExt = array();

    /**
     * Đường dẫn tạp tin cần tải
     * @var link 
     */
    protected $linkFile = '';

    /**
     * Tên file cần tải
     * @var type 
     */
    protected $fileName = '';

    /**
     * Dung luong tap tin can lay
     * @var int
     */
    protected $fileSize = 0;

    /**
     * Loại file
     * @var string 
     */
    protected $fileExt = '';

    /**
     * Đường dẫn đên thư mục lưu file
     * @var type 
     */
    protected $dirSaveFile = '';

    /**
     * Tên file khi lưu vào thư mục<br/> Mặc định để  trống hoặc NULL sẽ tạo tên tự động theo uniq
     * @var string 
     */
    protected $fileNameNew = '';

    /**
     * Thông báo lỗi
     * @var string 
     */
    protected $errMsg;

    /**
     * Quy định kích thước file tối đa hợp lệ
     * @var int 
     */
    protected $maxFileSize = 0;

    /**
     * Giới hạn file tối thiểu cho phép
     * @var int
     */
    protected $minFileSize = 0;

 

    function __construct()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        set_time_limit(0);
    }

    /**
     * Các tham số khi tải file
     * @param array $arrExt mảng định mở rộng file hợp lệ
     * @param int $maxSize Quy định kích thước tối đa cho phép, NULL hoặc 0 là  không giới hạn (đơn vị: kb)
     * @param int $minSize Quy định kích thước tối thiểu cho phép, NULL hoặc 0 là không giới hạn (Đơn vị: kb)
     */
    public function init(array $arrExt, $maxSize = NULL, $minSize = NULL)
    {
        foreach ($arrExt as &$ext)
        {
            $ext = trim($ext);
            $ext = strtolower($ext);
        }
        $this->arrExt = $arrExt;
        $this->maxFileSize = (int) $maxSize > 0 ? $maxSize : NULL;
        $this->minFileSize = (int) $minSize > 0 ? $minSize : NULL;

        return $this;
    }

    /**
     * Tải file
     * @param type $linkFile url file
     * @param type $dirSaveFile Thư mục lưu trữ file tải về
     * @param type $fileNameNew Tên mới
     * @return \stdClass
     */
    function downloadFile($linkFile, $dirSaveFile, $fileNameNew = '')
    {
        $this->linkFile = $linkFile;
        $this->dirSaveFile = $dirSaveFile;

        $this->fileNameNew = $fileNameNew;

        $this->_checkFileExists();

        if (empty($this->errMsg))
            $this->_checkExtension();

        if (empty($this->errMsg))
            $this->_checkFileSize();

        if (empty($this->errMsg))
            $this->_checkDir();

        if (empty($this->errMsg))
            $this->_downloadFile();

        $resp = new stdClass();
        if (empty($this->errMsg))
        {
            $resp->status = true;
            $resp->fileName = $this->fileNameNew . '.' . $this->fileExt;
        }
        else
        {
            $resp->status = FALSE;
            $resp->msgErr = $this->errMsg;
        }

        return $resp;
    }

    /**
     * Kiểm tra tập tin có tồn tại hay không?
     */
    private function _checkFileExists()
    {
        $arrInfoLink = explode('/', $this->linkFile);
        foreach ($arrInfoLink as &$item)
        {
            if ($item == 'http:' || $item == 'https:')
            {
                continue;
            }
            $item = urlencode($item);
        }
        $this->linkFile = implode('/', $arrInfoLink);
        @$head = array_change_key_case(get_headers($this->linkFile, TRUE));



        if (!$head OR $head[0] == 'HTTP/1.0 404 Not Found')
        {
            $this->errMsg = 'Đường dẫn tập tin không hợp lệ';
        }
        else
        {
            $this->fileSize = $head['content-length'];
        }
        return $this;
    }

    /**
     * kiểm tra định dạng file
     */
    private function _checkExtension()
    {
        $info = pathinfo($this->linkFile);
        $this->fileExt = strtolower($info['extension']);
        $this->filename = $info['filename'];
        if (!in_array($this->fileExt, $this->arrExt))
        {
            $this->errMsg = 'Định dạng tập tin không hợp lệ';
        }
        return $this;
    }

    /**
     * Kiểm tra dung lượng file
     */
    private function _checkFileSize()
    {
        if (
                ($this->maxFileSize != NULL && $this->maxFileSize < $this->fileSize)
                OR ( $this->maxFileSize != NULL && $this->minFileSize > $this->fileSize)
        )
        {
            $this->errMsg = 'Kích thường tập tin không hợp lệ';
        }
    }

    /**
     * Kiểm tra đường dẫn lưu tập tin
     * 1. Kiểm tra thư mục tồn tại hay không
     * 2. Kiểm tra quyền ghi file 
     */
    private function _checkDir()
    {
        $chkDir = is_dir($this->dirSaveFile);
        if (!$chkDir)
        {
            $mkDir = @mkdir($this->dirSaveFile, 0777, TRUE);
            if (!$mkDir)
                $this->errMsg = 'Tạo thư mục lưu trữ tập tin thất bại';
        }
    }

    /**
     * Lưu tập tin về thư mục
     * @param boolean $chkRate TRUE - Kiểm tra % download, FALSE: Không check
     */
    private function _downloadFile($chkRate = FALSE)
    {
        $newfname = $this->dirSaveFile . DIRECTORY_SEPARATOR . $this->fileNameNew . '.' . $this->fileExt;
        $file = fopen($this->linkFile, "rb");
        if ($file)
        {
            $newf = fopen($newfname, "wb");
            if ($newf)
            {
                while (!feof($file))
                {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    //echo "1MB File Chunk Written! ,<br/>";
                }
            }
        }

        if ($file)
            fclose($file);

        if ($newf)
            fclose($newf);

        if ($this->fileSize != filesize($newfname))
        {
            unlink($newfname);
            $this->errMsg = 'Xảy ra lỗi trong quá trình tải tập tin';
            return FALSE;
        }
        return TRUE;
    }

}


/* 
Example:
$transferFile = new transferFile();
var_dump($transferFile->init(array('7z'), NULL, NULL, TRUE)
                ->downloadFile('http://localhost/mot-cua/mot-cua.7z', 'D:\Zend\www\research\newfileNew.7z','abc'));

