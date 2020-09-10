<?php

namespace app\controllers;

use app\helper\QcloudCos;
use app\helper\Tool;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
    	return ['message' => 111];
//        return $this->render('index');
    }



	/**
	 * 萧山人在世界
	 * @return array
	 */
	public function actionXiaoshanList() {
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
		$cache = Yii::$app->cache;

		$data = $cache->get('world_xiaoshan');
		if(!$data){
			$data = Tool::httpRequest('http://txdzw-10015292.cos.myqcloud.com/xiaoshan.js');
			if(!$data){
				return ['code'=>-1,'msg'=>'原始文件获取失败'];
			}
			$data = substr($data, 11);
			$data = json_decode($data, true);
			if($data === NULL){
				return ['code'=>-1,'msg'=>'原始数据解析失败'];
			}
		}
		return ['code'=>0,'msg'=>'获取成功','data'=>$data];
	}

	public function actionXiaoshan(){
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
		$cache = Yii::$app->cache;
		$request = Yii::$app->request;

		$country = $request->get('country', '');
		$city = $request->get('city', '');
		$lat = $request->get('lat', '');
		$lon = $request->get('lon', '');
		if(empty($country) || empty($city) || empty($lat) || empty($lon)){
			return ['code'=>-1,'msg'=>'数据不完整'];
		}
		$data = $cache->get('world_xiaoshan');
		//$data = false;
		if(!$data){
			$data = Tool::httpRequest('http://txdzw-10015292.cos.myqcloud.com/xiaoshan.js');
			if(!$data){
				return ['code'=>-1,'msg'=>'原始文件获取失败'];
			}
			$data = substr($data, 11);
			$data = json_decode($data, true);
			if($data === NULL){
				return ['code'=>-1,'msg'=>'原始数据解析失败'];
			}
		}
		$country_exist = false;
		$city_exist = false;
		foreach($data as $key=>$val){
			if($val['country']==$country){
				$country_exist = true;
				$country_id = $key;
				foreach($val['city'] as $k=>$v){
					if($v['name']==$city) {
						$city_exist = true;
						if (!isset($data[$key]['city'][$k]['num'])) {
							$data[$key]['city'][$k]['num'] = 1;
						} else {
							$data[$key]['city'][$k]['num'] = $data[$key]['city'][$k]['num'] + 1;
						}
						break;
					}
				}
				break;
			}
		}
		if(!$country_exist){
			$data[] = [
				'country' => $country,
				'city' => [
					[
						'name' => $city,
						'lat' => $lat,
						'lon' => $lon,
						'num' => 1
					]
				],
			];
		}
		if($country_exist && !$city_exist){
			$data[$country_id]['city'][]=[
				'name' => $city,
				'lat' => $lat,
				'lon' => $lon,
				'num' => 1
			];
		}
		//var_dump($data);
		$cache->set('world_xiaoshan', $data);
		$data = json_encode($data);
		$data = "var data = ".$data;
		file_put_contents('/data/web/work/tool/web/public/xiaoshan.js',$data);
		$re = QcloudCos::delFile('txdzw', '/xiaoshan.js');
		if($re['code'] != 0){
			return ['code'=>-1,'msg'=>'原始数据删除失败'];
		}
		$re = QcloudCos::upload('txdzw','/data/web/work/tool/web/public/xiaoshan.js','/xiaoshan.js');
		if($re['code'] != 0){
			return ['code'=>-1,'msg'=>'原始数据更新失败'];
		}
		return ['code'=>0,'msg'=>'更新成功','data'=>$re['path']];
	}
}
