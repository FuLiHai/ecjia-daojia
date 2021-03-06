<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * ECJIA 订单查询条件类文件
 */
RC_Loader::load_app_class('order','orders', false);
class order_query extends order {
	private $where = array();//where条件数组
	
	public function __construct() {
		parent::__construct();
	}
	
	/* 已完成订单 */
	public function order_finished($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] = array(OS_CONFIRMED, OS_SPLITED);
		$where[$alias.'shipping_status'] = array(SS_RECEIVED);
		$where[$alias.'pay_status'] = array(PS_PAYED);
		$where[$alias.'is_delete'] = 0;
		return $where;
	}
	
	/* 待付款订单 */
	public function order_await_pay($alias = '') {
		$where = array();
		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		$payment_id_row = $payment_method->payment_id_list(false);
		$payment_id = "";
		foreach ($payment_id_row as $v) {
			$payment_id .= empty($payment_id) ? $v : ','.$v ;
		}
		
		/*货到付款订单不在待付款里显示*/
		$pay_cod_id = RC_DB::table('payment')->where('pay_code', 'pay_cod')->pluck('pay_id'); 
		if (!empty($pay_cod_id)) {
			$where[] = "pay_id != '.$pay_cod_id.'";
		}
		
		$payment_id = empty($payment_id) ? "''" : $payment_id;
    	$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED,OS_SPLITED);
        $where[$alias.'pay_status'] = PS_UNPAYED;
        $where[]= "( {$alias}shipping_status in (". SS_SHIPPED .",". SS_RECEIVED .") OR {$alias}pay_id in (" . $payment_id . ") )";
        $where[$alias.'is_delete'] = 0;
        return $where;
	}
	
	/* 待发货订单 */
	public function order_await_ship($alias = '') {
		$where = array();
		$payment_method = RC_Loader::load_app_class('payment_method','payment');
		//$payment_id_row = $payment_method->payment_id_list(true);
		/*货到付款需在待发货列表显示*/
		$pay_cod = RC_DB::table('payment')->where('pay_code', 'pay_cod')->pluck('pay_id');
		
		if (!empty($pay_cod)) {
			$where[] = "( ({$alias}order_status in (" . OS_UNCONFIRMED .",". OS_CONFIRMED.", ". OS_SPLITED.", ". OS_SPLITING_PART.")) OR ({$alias}pay_id in (" . $pay_cod . ") and {$alias}order_status in (" . OS_UNCONFIRMED .",". OS_CONFIRMED.", ". OS_SPLITED.", ". OS_SPLITING_PART.") ))";
		} else {
			$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART);
		}
		
		$where[$alias.'shipping_status'] = array(SS_UNSHIPPED, SS_SHIPPED_PART, SS_PREPARING, SS_SHIPPED_ING, OS_SHIPPED_PART);
		
		if (!empty($pay_cod)) {
			$where[] = "( {$alias}pay_status in (" . PS_PAYED .",". PS_PAYING.") OR {$alias}pay_id in (" . $pay_cod . "))";
		} else {
			$where[] = "( {$alias}pay_status in (" . PS_PAYED .",". PS_PAYING."))";
		}
		$where[$alias.'is_delete'] = 0;
		return $where;
	}
	/* 收货确认订单 */
	public function order_received($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] = array(OS_SPLITED);
		$where[$alias.'shipping_status'] = array(SS_RECEIVED);
		$where[$alias.'pay_status'] = array(PS_PAYED, PS_PAYING);
		$where[$alias.'is_delete'] = 0;
		return $where;
	}
	
	/* 未确认订单 */
	public function order_unconfirmed($alias = '') {
		$where = array();
		$where[$alias.'order_status'] = OS_UNCONFIRMED;
		$where[$alias.'is_delete'] = 0;
		return $where;
	}
	
	/* 未处理订单：用户可操作 */
	public function order_unprocessed($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] =  array(OS_UNCONFIRMED, OS_CONFIRMED);
        $where[$alias.'shipping_status'] = SS_UNSHIPPED;
        $where[$alias.'pay_status'] = PS_UNPAYED;
        $where[$alias.'is_delete'] = 0;
		return $where;
	}
	
	/* 未付款未发货订单：管理员可操作 */
	public function order_unpay_unship($alias = '') {
		$where = array();
    	$where[$alias.'order_status'] = array(OS_UNCONFIRMED, OS_CONFIRMED);
        $where[$alias.'shipping_status'] = array(SS_UNSHIPPED, SS_PREPARING);
        $where[$alias.'pay_status'] = PS_UNPAYED;
        $where[$alias.'is_delete'] = 0;
        return $where;
	}
	
	/* 已发货订单：不论是否付款 */
	public function order_shipped($alias = '') {
		$where = array();
        $where[$alias.'order_status'] = array(OS_CONFIRMED, OS_SPLITED);
        $where[$alias.'shipping_status'] = array(SS_SHIPPED);
        $where[$alias.'is_delete'] = 0;
        return $where;
	}

	/* 退货*/
    public function order_refund($alias = '') {
    	$where = array();
        $where[$alias.'order_status'] = OS_RETURNED;
        $where[$alias.'is_delete'] = 0;
        return $where;
    }
    
    /* 无效*/
    public function order_invalid($alias = '') {
    	$where = array();
        $where[$alias.'order_status'] = OS_INVALID;
        $where[$alias.'is_delete'] = 0;
        return $where;
    }
    
    /* 取消*/
    public function order_canceled($alias = '') {
    	$where = array();
    	$where[$alias.'order_status'] = OS_CANCELED;
    	$where[$alias.'is_delete'] = 0;
    	return $where;
    }

	public function order_where($filter) {
		if ($filter['keywords']) {
			$this->where[] = "o.order_sn like '%".mysql_like_quote($filter['keywords'])."%' or o.consignee like '%".mysql_like_quote($filter['keywords'])."%'";
		} else {
			if ($filter['order_sn']) {
	        	$this->where['o.order_sn'] = array('like' => '%'.mysql_like_quote($filter['order_sn']).'%');
	        }
	        if ($filter['consignee']) {
	        	$this->where['o.consignee'] = array('like' => '%'.mysql_like_quote($filter['consignee']).'%');
	        }
		}
		
		if ($filter['merchant_keywords']) {
			$this->where['s.merchants_name'] = array('like' => '%'.mysql_like_quote($filter['merchant_keywords']).'%');
		}
		
        if ($filter['email']) {
        	$this->where['o.email'] = array('like' => '%'.mysql_like_quote($filter['email']).'%');
        }
        if ($filter['address']) {
        	$this->where['o.address'] = array('like' => '%'.mysql_like_quote($filter['address']).'%');
        }
        if ($filter['zipcode']) {
        	$this->where['o.zipcode'] = array('like' => '%'.mysql_like_quote($filter['zipcode']).'%');
        }
        if ($filter['tel']) {
        	$this->where['o.tel'] = array('like' => '%'.mysql_like_quote($filter['tel']).'%');
        }
        if ($filter['mobile']) {
        	$this->where['o.mobile'] = array('like' => '%'.mysql_like_quote($filter['mobile']).'%');
        }
        if ($filter['merchants_name']) {
        	$this->where['s.merchants_name'] = array('like' => '%'.mysql_like_quote($filter['merchants_name']).'%');
        }
        if ($filter['country']) {
        	$this->where['o.country'] = $filter['country'];
        }
        if ($filter['province']) {
        	$this->where['o.province'] = $filter['province'];
        }
        if ($filter['city']) {
        	$this->where['o.city'] = $filter['city'];
        }
        if ($filter['district']) {
        	$this->where['o.district'] = $filter['district'];
        }
        if ($filter['shipping_id']) {
        	$this->where['o.shipping_id'] = $filter['shipping_id'];
        }
        if ($filter['pay_id']) {
        	$this->where['o.pay_id'] = $filter['pay_id'];
        }
        if ($filter['status'] != -1) {
        	$this->where[] = " (o.order_status  = '$filter[status]' or o.shipping_status  = '$filter[status]' or o.pay_status  = '$filter[status]')";
        }
        if ($filter['order_status'] != -1) {
        	$this->where['o.order_status'] = $filter['order_status'];
        }
        if ($filter['shipping_status'] != -1) {
        	$this->where['o.shipping_status'] = $filter['shipping_status'];
        }
        if ($filter['pay_status'] != -1) {
        	$this->where['o.pay_status'] = $filter['pay_status'];
        }
        if ($filter['user_id']) {
        	$this->where['o.user_id'] = $filter['user_id'];
        }
        if ($filter['user_name']) {
        	$this->where['u.user_name'] = array('like'=> '%'.mysql_like_quote($filter['user_name']).'%');
        }
        if ($filter['start_time']) {
        	$this->where[] = "o.add_time >= '$filter[start_time]'";
        }
        if ($filter['end_time']) {
        	$this->where[] = "o.add_time <= '$filter[end_time]'";
        }
		/* 团购订单 */
        if ($filter['group_buy_id']) {
        	$this->where['o.extension_code'] = 'group_buy';
        	$this->where['o.extension_id'] = $filter['group_buy_id'];
        }
		return $this->where;
	}
	
    public function get_order_list($pagesize = '15') {
	    $args = $_GET;
	   
        /* 过滤信息 */
        $filter['order_sn'] 			= empty($args['order_sn']) 			? '' 	: trim($args['order_sn']);
        $filter['consignee'] 			= empty($args['consignee']) 		? '' 	: trim($args['consignee']);
        $filter['keywords']				= empty($args['keywords'])			? '' 	: trim($args['keywords']);
        $filter['merchant_keywords']	= empty($args['merchant_keywords'])	? '' 	: trim($args['merchant_keywords']);
        $filter['email'] 				= empty($args['email']) 			? '' 	: trim($args['email']);
        $filter['address'] 				= empty($args['address']) 			? '' 	: trim($args['address']);
        $filter['zipcode'] 				= empty($args['zipcode']) 			? '' 	: trim($args['zipcode']);
        $filter['tel'] 					= empty($args['tel']) 				? '' 	: trim($args['tel']);
        $filter['mobile'] 				= empty($args['mobile']) 			? 0 	: intval($args['mobile']);
        $filter['merchants_name'] 		= empty($args['merchants_name']) 	? '' 	: trim($args['merchants_name']);
        
       	$filter['country'] 				= empty($args['country']) 			? ''    : trim($args['country']);
        $filter['province'] 			= empty($args['province']) 			? '' 	: trim($args['province']);
        $filter['city'] 				= empty($args['city']) 				? '' 	: trim($args['city']);
        $filter['district'] 			= empty($args['district']) 			? '' 	: trim($args['district']);
        $filter['street'] 			    = empty($args['street']) 			? '' 	: trim($args['street']);

        $filter['shipping_id'] 			= empty($args['shipping_id']) 		? 0 	: intval($args['shipping_id']);
        $filter['pay_id'] 				= empty($args['pay_id']) 			? 0 	: intval($args['pay_id']);
        
        $filter['order_status'] 		= isset($args['order_status']) 		? intval($args['order_status']) 		: -1;
        $filter['status'] 		        = isset($args['status']) 		    ? intval($args['status']) 				: -1;
        $filter['shipping_status'] 		= isset($args['shipping_status']) 	? intval($args['shipping_status']) 		: -1;
        $filter['pay_status'] 			= isset($args['pay_status']) 		? intval($args['pay_status']) 			: -1;
        $filter['user_id'] 				= empty($args['user_id']) 			? 0 									: intval($args['user_id']);
        $filter['user_name'] 			= empty($args['user_name']) 		? '' 									: trim($args['user_name']);
        $filter['composite_status'] 	= isset($args['composite_status']) 	? intval($args['composite_status']) 	: -1;
        $filter['group_buy_id'] 		= isset($args['group_buy_id']) 		? intval($args['group_buy_id']) 		: 0;
        $filter['sort_by'] 				= empty($args['sort_by']) 			? 'add_time' 							: trim($args['sort_by']);
        $filter['sort_order'] 			= empty($args['sort_order']) 		? 'DESC' 								: trim($args['sort_order']);
        
        $filter['start_time'] 			= empty($args['start_time']) 		? '' : RC_Time::local_strtotime($_GET['start_time']);
        $filter['end_time'] 			= empty($args['end_time']) 			? '' : RC_Time::local_strtotime($_GET['end_time']) + 86399;
		$filter['type']					= empty($args['type']) 				? '' : $args['type']; 
        
        /* 团购订单 */
        if ($filter['group_buy_id']) {
        	$this->where = array('o.extension_code' => 'group_buy', 'o.extension_id' => $filter['group_buy_id']);
        }
        
		$this->where = array_merge($this->where, $this->order_where($filter));

        //综合状态
        switch($filter['composite_status']) {
            case CS_AWAIT_PAY :
				$this->where = array_merge($this->where,$this->order_await_pay());
                break;

            case CS_AWAIT_SHIP :
				$this->where = array_merge($this->where,$this->order_await_ship());
                break;
                
            case CS_FINISHED :
				$this->where = array_merge($this->where,$this->order_finished());
                break;

            case CS_RECEIVED :
                $this->where = array_merge($this->where,$this->order_received());
                break;
                
            case CS_SHIPPED : 
            	$this->where = array_merge($this->where,$this->order_shipped());
            	break;

            case PS_PAYING :
                if ($filter['composite_status'] != -1) {
                    $this->where['o.pay_status'] = $filter['composite_status'];
                }
                break;
            case OS_SHIPPED_PART :
                if ($filter['composite_status'] != -1) {
                	$this->where['o.shipping_status'] = $filter['composite_status']-2;
                }
                break;
            default:
                if ($filter['composite_status'] != -1) {
                	$this->where['o.order_status'] = $filter['composite_status'];
                }
        };
		
		RC_Cookie::set('composite_status', $filter['composite_status']);
        
        $db_order_info = RC_DB::table('order_info as o')
        	->leftJoin('users as u', RC_DB::raw('o.user_id'), '=', RC_DB::raw('u.user_id'))
        	->leftJoin('store_franchisee as s', RC_DB::raw('o.store_id'), '=', RC_DB::raw('s.store_id'));
    	
        if (is_array($this->where)) {
        	foreach ($this->where as $k => $v) {
        		if (!is_numeric($k)) {
        			if (is_array($v)) {
        				if (array_get($v, 'like')) {
        					foreach ($v as $key => $val) {
        						if ($key == 'like') {
        							$db_order_info->where(RC_DB::raw($k), 'like', $val);
        						}
        					}
        				} else {
        					$db_order_info->whereIn(RC_DB::raw($k), $v);
        				}
        			} else {
        				$db_order_info->where(RC_DB::raw($k), $v);
        			}
        		} else {
        			$db_order_info->whereRaw('('.$v.')');
        		}
        	}
        }
        
        //is_delete 为0的为没删除的
        $db_order_info->where(RC_DB::raw('o.is_delete'), 0);
        
        $filter_count = $db_order_info
	        ->select(RC_DB::raw('count(*) as count'), RC_DB::raw('SUM(IF(s.manage_mode = "self", 1, 0)) as self'))
	        ->first();
        
        if (!empty($filter['type'])) {
        	$db_order_info->where(RC_DB::raw('s.manage_mode'), 'self');
        }
        
        $count = $db_order_info->count();
        $page = new ecjia_page($count, $pagesize, 6);
        $filter['record_count'] = $count;

		$fields = "o.order_id, o.store_id, o.order_sn, 
		o.add_time, o.order_status, o.shipping_status, 
		o.order_amount, o.money_paid, o.pay_status, 
		o.consignee, o.address, o.email, o.tel, o.mobile, 
		o.extension_code, o.extension_id ,(" . $this->order_amount_field('o.') . ") AS total_fee, 
		o.surplus, o.integral_money, o.bonus, 
		s.merchants_name, u.user_name";
    	
    	$row = $db_order_info
    		->leftJoin('order_goods as og', RC_DB::raw('o.order_id'), '=', RC_DB::raw('og.order_id'))
    		->selectRaw($fields)
    		->orderby($filter['sort_by'], $filter['sort_order'])
    		->take($pagesize)
    		->skip($page->start_id-1)
    		->groupby(RC_DB::raw('o.order_id'))
    		->get();
    	
    	foreach (array('order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name') AS $val) {
            $filter[$val] = stripslashes($filter[$val]);
        }
		RC_Loader::load_app_func('global', 'goods');
		
		RC_Loader::load_app_func('admin_goods', 'goods');
		$order_deposit = 0;

		$order = array();
        /* 格式话数据 */
	    if (!empty($row)) {
	        foreach ($row AS $key => $value) {
	            $order[$value['order_id']]['formated_order_amount'] = $value['extension_code'] == 'group_buy' ? price_format($value['total_fee']-$value['money_paid']-$value['surplus']-$value['integral_money']-$value['bonus']) : price_format($value['order_amount']);
				$order[$value['order_id']]['formated_money_paid'] 	= price_format($value['money_paid']);
				$order[$value['order_id']]['formated_total_fee'] 	= price_format($value['total_fee']);
	            $order[$value['order_id']]['short_order_time']		= RC_Time::local_date('Y-m-d H:i', $value['add_time']);
                $order[$value['order_id']]['user_name']             = empty($value['user_name']) ? RC_Lang::get('orders::order.anonymous') : $value['user_name'];
                $order[$value['order_id']]['order_id']              = $value['order_id'];
                $order[$value['order_id']]['order_sn']              = $value['order_sn'];
                $order[$value['order_id']]['add_time']              = $value['add_time'];
                $order[$value['order_id']]['order_status']          = $value['order_status'];
                $order[$value['order_id']]['shipping_status']       = $value['shipping_status'];
                $order[$value['order_id']]['order_amount']         	= $value['order_amount'];
                $order[$value['order_id']]['money_paid']         	= $value['money_paid'];
                $order[$value['order_id']]['pay_status']         	= $value['pay_status'];
                $order[$value['order_id']]['consignee']         	= $value['consignee'];
                $order[$value['order_id']]['email']         		= $value['email'];
                $order[$value['order_id']]['tel']         			= $value['tel'];
                $order[$value['order_id']]['mobile']         		= $value['mobile'];
                $order[$value['order_id']]['extension_code']        = $value['extension_code'];
                $order[$value['order_id']]['extension_id']         	= $value['extension_id'];
                $order[$value['order_id']]['total_fee']         	= $value['total_fee'];
                $order[$value['order_id']]['merchants_name']        = $value['merchants_name'];
			 
				$group_buy = group_buy_info($value['extension_id']);
				if ($group_buy['deposit'] > 0) {
					$order_deposit = price_format($value['goods_number']*$group_buy['deposit']);
				}
				$order[$value['order_id']]['formated_bond'] = $order_deposit;

	            if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
	                /* 如果该订单为无效或取消则显示删除链接 */
	                $order[$value['order_id']]['can_remove'] = 1;
	            } else {
	                $order[$value['order_id']]['can_remove'] = 0;
	            }
	        }
	    }
	    
	   	return array('orders' => $order, 'filter' => $filter, 'page' => $page->show(2), 'desc' => $page->page_desc(), 'count' => $filter_count);
    }
	
	/**
	 * 生成查询订单总金额的字段
	 * @param   string  $alias  order表的别名（包括.例如 o.）
	 * @return  string
	 */
	function order_amount_field($alias = '') {
	    return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
	           " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
	           " + {$alias}card_fee ";
	}
}

// end