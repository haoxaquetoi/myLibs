<?php

$url = "http://mdm.bacgiang.gov.vn/ContentFolder/HoSoFileDinhKem/source_files/2016/10/04/17235373_QĐ_GQ_16-10-04.docx";
$arrExt = array('xlsx', 'pdf', 'png', 'Docx');

$instance = new downloadAttachments();
$instance->init($arrExt, null, null);

$instance->download($url, __DIR__);
$instance->download($url, __DIR__);

/**
 * Tải tập tin từ link url online
 */
class downloadAttachment
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
    function download($linkFile, $dirSaveFile, $fileNameNew = '')
    {
        $this->linkFile = $linkFile;
        $this->dirSaveFile = $dirSaveFile;
        $this->fileNameNew = ($fileNameNew != '') ? $fileNameNew : uniqid() . '-' . date('ymdhis');

        $this->_checkFileExists();

        if (empty($this->errMsg))
            $this->_checkExtension();

        if (empty($this->errMsg))
            $this->_checkFileSize();

        if (empty($this->errMsg))
            $this->_checkDir();

        if (empty($this->errMsg))
            $this->_getFileContent();

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
     */
    private function _getFileContent()
    {
        $file = @file_get_contents($this->linkFile);
        if ($file === FALSE)
        {
            $this->errMsg = 'Xảy ra lỗi trong quá trình tải tập tin';
            return $this;
        }
        $fileSizePush = file_put_contents($this->dirSaveFile . DIRECTORY_SEPARATOR . $this->fileNameNew . '.' . $this->fileExt, $file);
        if ($fileSizePush == FALSE || $fileSizePush != $this->fileSize)
        {
            $this->errMsg = 'Xảy ra lỗi trong quá trình tải tập tin';
        }
    }

}
