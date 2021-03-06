<?php
/**
 * 淘宝登录授权认证
 */
class TaoBaoAuthController extends AppController
{
    public function __construct() {
        $this ->status = 2;
        parent::__construct();
    }
    public function authInfo()
    {
        /**
         * 淘宝Id为空 则取消授权 否则就是 授权认证
         * 取消授权: uid表淘宝昵称该为空 用户头像改为默认
         */
        $data = $this->dparam;
        if(empty($data['taobao_id']) && !empty($data['user_id'])) {
            $arr = [
                'taobao_auth' => 0,
                'nickname'    => $data['user_id'],
                'head_img'    => RES_SITE."shoppingResource/head/".rand(1,2).".jpg",
            ];
            M('uid')->where(['objectId' => ['=',$data['user_id']]])->save($arr) ? info('已成功取消授权',1) : info('取消授权失败',-1);

        } else if(isset($data['imei'], $data['deviceVer'], $data['bdid'], $data['idfa'], $data['uuid'])) {
            if(empty($data['user_id']) || empty($data['taobao_id']) || empty($data['user_name']) || empty($data['user_head_img']))
                info('缺少参数',-1);

            try {
                M()->startTrans();
                $add = [
                    'nick'          => $data['user_name'],
                    'taobao_id'     => $data['taobao_id'],
                    'uid'           => $data['user_id'],
                    'taobao_auth'   => 1,
                    'head_img'      => $data['user_head_img'],
                    'nickname'      => $data['user_name'],
                ];
                /**
                 * [查询该淘宝ID信息 是否已经存在taobao表中 不存在添加淘宝id信息]
                 */
                if(!M('taobao')->where(['taobao_id' => ['=', $data['taobao_id']]])->select('single'))
                    M('taobao')->add($add) or E('授权失败');
                //获取到用户did_id
                $did_id = M('did_log')->where("(bdid='{$data['bdid']}' OR idfa='{$data['idfa']}' AND uuid='{$data['uuid']}') OR (imei='{$data['imei']}')")->select('single');
                if(isset($did_id['id']))
                    $did_id = $did_id['id'];
                else
                    E('没能获取到设备id');
                /**
                 * 绑定淘宝账号与用户之间的关系 如果之前绑定过一样的则不需要再添加
                 */
                $where = [
                    'uid'       => ['=', $data['user_id']],
                    'taobao_id' => ['=', $data['taobao_id']],
                    ['and']
                ];
                $add['did_id'] = $did_id;

                if(!M('taobao_log')->where($where)->select('single')) {
                    M('taobao_log')->add($add) or E('第二步授权失败');
                }
                /**
                 * 修改用户授权状态 用户头像 用户昵称
                 */
                M('uid')->where(['objectId' => ['=',$data['user_id']]])->save($add) or E('第三步授权失败');

            } catch(Exception $e) {
                M()->rollback();
                info($e->getMessage());
            }
            M()->commit();
            //授权成功之后检测如果该账户授权过的淘宝账号只有一次记录的 则就去绑定好友关系
            $a = M('taobao_log')->where(['uid' => ['=', $data['user_id']] ])->field('taobao_id')->select('single');
            $count = M('taobao_log')->where(['taobao_id' => ['=', $a['taobao_id']]])->count();
            if($count == 1) {
                //判断该账户是否是通过邀请页过来的 并且还没有绑定师傅
                $user = new UserController;
                $phone = M('uid')->where(['objectId' => ['=', $data['user_id']]])->field('phone')->select('single');
                $user_info = $user->returnBindSfuid($phone['phone']);
                if(!empty($user_info)) {
                    //通过用户被邀请手机号 去uid表查询到该用户uid 进行好友关系绑定
                    $data = M('uid')->where(['phone' => ['=',$user_info['phone']]])->field('objectId')->select('single');
                    if(!empty($data))
                        if($user->bindMasters(true,$data['objectId'],$user_info['sfuid']) === true)
                            M('friend_log')->where(['phone' => ['=',$phone['phone']]])->save(['status' => 2]);
                }
            }
            info('授权成功',1);
        }
        info('缺少参数',-1);
    }
}