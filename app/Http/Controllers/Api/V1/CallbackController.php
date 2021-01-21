<?php
/**
 * CallbackController
 * @author DELL
 * 2021/1/19 15:47
 **/

namespace App\Http\Controllers\Api\V1;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\VideoItemRepository;

class CallbackController extends Controller
{
    const SUCCESS = 0;

    // 此处前缀为七牛控制台配置的工作流输出路径前缀
    const THUMB_PREFIX = 'video-thumb/';
    const HLS_PREFIX = 'hls/640/';
    const HLS_HD_PREFIX = 'hls/1280/';

    public $videoItemRepository;

    public function __construct(VideoItemRepository $itemRepository)
    {
        $this->videoItemRepository = $itemRepository;
    }

    public function qiniu(Request $request)
    {
        $data = $request->all();

        if (!$this->qiniuValidateData($data)) {
            return false;
        }

        $key = $data['input']['kodo_file']['key'];
        $videoItem = $this->videoItemRepository->findby('video_key', $key);

        if ($data['code'] === self::SUCCESS) {
            if (!$videoItem) {
                return false;
            }

            // 处理七牛返回数据
            $value = [];
            $ops = $data['ops'];
            foreach ($ops as $item) {
                if ($item['fop']['result']['has_output'] == 1) {
                    $value[] = $item['fop']['result']['kodo_file'];
                }
            }

            // 更新数据库
            $cdnUrlVideo = config('filesystems.qiniu.cdnUrlVideo');
            foreach ($value as $v) {
                if (strpos($v['key'], self::THUMB_PREFIX) === 0) {
                    $videoItem->poster = $cdnUrlVideo.'/'.$v['key'];
                    continue;
                }
                if (strpos($v['key'], self::HLS_PREFIX) === 0) {
                    $videoItem->hls_url = $cdnUrlVideo.'/'.$v['key'];
                    continue;
                }
                if (strpos($v['key'], self::HLS_HD_PREFIX) === 0) {
                    $videoItem->hls_hd_url = $cdnUrlVideo.'/'.$v['key'];
                    continue;
                }
            }

            $videoItem->is_transcode = self::SUCCESS;
            $videoItem->save();

        } else {
            // 转码失败处理 transcode=3
            $videoItem->is_transcode = $data['code'];
            $videoItem->save();
        }
    }

    /**
     * 一些简单的鉴权，防止刷接口
     *
     * @param $data
     * @return bool
     */
    private function qiniuValidateData($data)
    {
        if (is_array($data)
            && array_key_exists('pipeline', $data)
            && strstr($data['pipeline'], env('VideoPipelineHls'))
        ) {
            return true;
        }

        return false;
    }

}
