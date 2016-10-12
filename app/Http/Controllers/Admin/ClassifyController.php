<?php
/******************************************
****AuThor:rubbish@163.com
****Title :栏目分类
*******************************************/
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

//使用Class模型
use App\Http\Model\Classify;
use DB;
//使用URL生成地址
use URL;
//使用自定义第三方类库:分类列表数据 
use App\Common\lib\Cates; 

// 导入 Intervention Image Manager Class
use Intervention\Image\ImageManagerStatic as Image;


class ClassifyController extends PublicController
{
    /******************************************
	****AuThor:rubbish@163.com
	****Title :列表
	*******************************************/
	public function index()  
	{
		$website=$this->website;
		$website['cursitename']=trans('admin.website_navigation_classify');
		$website['apiurl_list']=URL::action('Admin\ClassifyController@api_list');
		$website['apiurl_get_one']=URL::action('Admin\ClassifyController@api_get_one');
		$website['link_add']=URL::action('Admin\ClassifyController@add');
		$website['link_edit']='/admin/classify/edit/';
		$website['way']='name';
		$wayoption[]=array('text'=>trans('admin.website_classify_item_name'),'value'=>'name');
		$website['wayoption']=json_encode($wayoption);
		$website['modellist']=$this->modellist;
		return view('admin/classify/index')->with('website',$website);
	}
	/******************************************
	****AuThor:rubbish@163.com
	****Title :添加
	*******************************************/
	public function add()
	{
		$website=$this->website;
		$website['cursitename']=trans('admin.website_navigation_classify');
		$website['apiurl_add']=URL::action('Admin\ClassifyController@api_add');
		$website['apiurl_info']=URL::action('Admin\ClassifyController@api_info');
		$website['apiurl_edit']=URL::action('Admin\ClassifyController@api_edit');
		$website['apiurl_del_image']=URL::action('Admin\ClassifyController@api_del_image');
		$website['id']=0;
		$website['modellist']=json_encode($this->modellist);
		
		$list=object_array(DB::table('classifies')->where('status','=','1')->orderBy('id', 'desc')->get());
		if($list)
		{
			$cates=new Cates();
			$cates->opt($list);
			$classopts = $cates->opt;
			$classoptsdata = $cates->optdata;
			$website['classlist']=json_encode($classoptsdata);
		}
		else
		{
			$classlist[]=array('text'=>trans('admin.website_select_default'),'value'=>'0');
			$website['classlist']=json_encode($classlist);
		}

		return view('admin/classify/add')->with('website',$website);
	}
	/******************************************
	****AuThor : rubbish@163.com
	****Title  : 编辑信息
	*******************************************/
	public function edit($id)  
	{
		$website=$this->website;
		$website['cursitename']=trans('admin.website_navigation_classify');
		$website['apiurl_add']=URL::action('Admin\ClassifyController@api_add');
		$website['apiurl_info']=URL::action('Admin\ClassifyController@api_info');
		$website['apiurl_edit']=URL::action('Admin\ClassifyController@api_edit');
		$website['apiurl_del_image']=URL::action('Admin\ClassifyController@api_del_image');
		
		$website['id']=$id;
		$website['modellist']=json_encode($this->modellist);

		$list=object_array(DB::table('classifies')->where('status','=','1')->orderBy('id', 'desc')->get());
		if($list)
		{
			$cates=new Cates();
			$cates->opt($list);
			$classopts = $cates->opt;
			$classoptsdata = $cates->optdata;
			$website['classlist']=json_encode($classoptsdata);
		}
		else
		{
			$classlist[]=array('text'=>trans('admin.website_select_default'),'value'=>'0');
			$website['classlist']=json_encode($classlist);
		}
		return view('admin/classify/add')->with('website',$website);
	}
	/******************************************
	****AuThor:rubbish@163.com
	****Title :列表接口
	*******************************************/
	public function api_list(Request $request)  
	{
		$search_field=$request->get('way')?$request->get('way'):'name';
		$keyword=$request->get('keyword');
		if($keyword)
		{
			$list=Classify::where($search_field, 'like', '%'.$keyword.'%')->paginate($this->pagesize);
			//分页传参数
			$list->appends(['keyword' => $keyword,'way' =>$search_field])->links();
		}
		else
		{
			$list=Classify::paginate($this->pagesize);
			
		}
		if($list)
		{
			$cates=new Cates();
			$cates->opt($list);
			$classoptlist = $cates->optlist;
			$list['cates']=$classoptlist;

			$msg_array['status']='1';
			$msg_array['info']=trans('admin.website_get_success');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']=$list;
			$msg_array['param_way']=$search_field;
			$msg_array['param_keyword']=$keyword;
		}
		else
		{
			$msg_array['status']='1';
			$msg_array['info']=trans('admin.website_get_empty');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']="";
			$msg_array['param_way']=$search_field;
			$msg_array['param_keyword']=$keyword;
		}
        return response()->json($msg_array);
	}
	/******************************************
	****AuThor:rubbish@163.com
	****Title :添加接口
	*******************************************/
	public function api_add(Request $request)  
	{

		$params = new Classify;
		$params->modelid 	= $request->get('modelid');
		$params->topid 		= $request->get('topid');
		$params->name 		= $request->get('name');
		$params->orderid	= $request->get('orderid');
		$params->linkurl	= $request->get('linkurl');
		$params->navflag	= $request->get('navflag');
		$params->perpage	= $request->get('perpage');
		$params->status		= $request->get('status');

		if($params->topid == 0)
		{
			$params->grade=1;
		}
		else
		{
			$classify_info=Classify::find($params->topid);
			$params->grade=$classify_info['grade']+1;	
		}

		if ($params->save()) 
		{
			

			if($params->topid ==0 && $params->grade==1)
			{ 
					$params->node='';
					$params->bcid=$params->id;
			}
			else
			{
				$params->bcid=$params->topid;
				if($params->grade==2)
				{
					$params->node=$params->topid.','.$params->id;
				}
				else
				{
					$params->node= $classify_info['node'].','.$params->id;
				}
				$params->scid=$params->id;
			}
			$params->save();

			$msg_array['status']='1';
			$msg_array['info']=trans('admin.website_add_success');
			$msg_array['is_reload']=0;
			$msg_array['curl']=URL::action('Admin\ClassifyController@index');
			$msg_array['resource']='';
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';
		} 
		else 
		{
			$msg_array['status']='0';
			$msg_array['info']=trans('admin.website_add_failure');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']="";
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';	

		}	

        return response()->json($msg_array);

	}
	/******************************************
	****AuThor:rubbish@163.com
	****Title :详情接口
	*******************************************/
	public function api_info(Request $request)  
	{

		$condition['id']=$request->get('id');
		$info=DB::table('classifies')->where($condition)->first();
		if($info)
		{
			$msg_array['status']='1';
			$msg_array['info']=trans('admin.website_get_success');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']=$info;
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';
		}
		else
		{
			$msg_array['status']='0';
			$msg_array['info']=trans('admin.website_get_empty');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']="";
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';
		}
        return response()->json($msg_array);
	}
	/******************************************
	****@AuThor : rubbish@163.com
	****@Title  : 更新数据接口
	****@return : Response
	*******************************************/
	public function api_edit(Request $request)
	{

		$params = Classify::find($request->get('id'));
		$params->modelid 	= $request->get('modelid');
		$params->topid 		= $request->get('topid');
		$params->name 		= $request->get('name');
		$params->orderid	= $request->get('orderid');
		$params->linkurl	= $request->get('linkurl');
		$params->navflag	= $request->get('navflag');
		$params->perpage	= $request->get('perpage');
		$params->status		= $request->get('status');

		if($params->topid==0)
		{
			$params->grade=1;
			$params->node= '';
			$params->bcid=$request->get('id');
		}
		else
		{
			$classify_info=Classify::find($params->topid);
			$params->grade=$classify_info['grade']+1;	

			$params->bcid=$params->topid;	

			if($params->grade==2)
			{
				$params->node=$params->topid.','.$params->id;
			}
			else
			{
				$params->node= $classify_info['node'].','.$params->id;
			}
			$params->scid=$params->id;	
		}

		//图片上传处理接口
		$attachment='attachment';
		if($request->get($attachment))
		{
			//上传文件类别名称
			$classname='Classify';
			// 引入 composer autoload
			require base_path('vendor').'/autoload.php';
			//上传文件夹路径
			$uploads_dir=public_path('uploads');
			//上传日期时间
			$datetime=date('YmdHis');
			//水印图片路径
			$watermark_dir=public_path('watermark').'/logo.png';
			//保存文件名
			$filename=$uploads_dir.'/'.$classname.'/'.$datetime.'.jpg';
			$watermark_filename=$uploads_dir.'/'.$classname.'/watermark/'.$datetime.'.jpg';
			$thumb_filename=$uploads_dir.'/'.$classname.'/thumb/'.$datetime.'.jpg';

			if($this->is_watermark==1)
			{
				// 合成水印
				$img = Image::make($request->get($attachment))->insert($watermark_dir, 'bottom-right', 15, 10)->save($watermark_filename);
			}
			if($this->is_thumb==1)
			{
				// 生成缩略图
				$img = Image::make($request->get($attachment))->resize(200, 200)->save($thumb_filename);
			}
			// 将处理后的图片重新保存到其他路径
			Image::make($request->get($attachment))->save($filename);
			
			$params->attachment=$datetime.'.jpg';
			$params->isattach=1;
		}

		if ($params->save()) 
		{
			$msg_array['status']='1';
			$msg_array['info']=trans('admin.website_save_success');
			$msg_array['is_reload']=0;
			$msg_array['curl']=URL::action('Admin\ClassifyController@index');
			$msg_array['resource']='';
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';
		} 
		else 
		{
			$msg_array['status']='0';
			$msg_array['info']=trans('admin.website_save_failure');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']="";
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';	
		}
		return response()->json($msg_array);
	}
	/******************************************
	****AuThor:rubbish@163.com
	****Title :获取一键操作接口
	*******************************************/
	public function api_get_one(Request $request)  
	{
		$params = Classify::find($request->get('id'));
		switch ($request->get('fields')) 
		{
			//扩展接口方法
			case 'status':
						$params->status=($params->status==1?0:1);

						if ($params->save()) 
						{
							$msg_array['status']='1';
							$msg_array['info']=trans('admin.website_action_set_success');
							$msg_array['is_reload']=0;
							$msg_array['curl']=URL::action('Admin\ClassifyController@index');
							$msg_array['resource']='';
							$msg_array['param_way']='';
							$msg_array['param_keyword']='';
						} 
						else 
						{
							$msg_array['status']='0';
							$msg_array['info']=trans('admin.website_action_set_failure');
							$msg_array['is_reload']=0;
							$msg_array['curl']='';
							$msg_array['resource']="";
							$msg_array['param_way']='';
							$msg_array['param_keyword']='';	
						}

				break;
			
			default:
				# code...
				break;
		}

        return response()->json($msg_array);

	}
	/******************************************
	****@AuThor : rubbish@163.com
	****@Title  : 更新数据接口
	****@return : Response
	*******************************************/
	public function api_del_image(Request $request)
	{
		switch ($request->get('classname')) 
		{
			case 'Classify':
				$params = Classify::find($request->get('id'));
				$classname=$request->get('classname');
				# code...
				break;
			
			default:
				# code...
				break;
		}
		if($params['isattach']==1)
		{
			//上传文件夹路径
			$uploads_dir=public_path('uploads');
			//保存文件名
			$filename=$uploads_dir.'/'.$classname.'/'.$params['attachment'];
			$watermark_filename=$uploads_dir.'/'.$classname.'/watermark/'.$params['attachment'];
			$thumb_filename=$uploads_dir.'/'.$classname.'/thumb/'.$params['attachment'];

			
			if (file_exists($watermark_filename)) 
			{
			    unlink ($watermark_filename);
			}
			if (file_exists($thumb_filename)) 
			{
			    unlink ($thumb_filename);
			}
			if (file_exists($filename)) 
			{
			    $result=unlink ($filename);
			    if($result)
			    {
			    	$params->attachment='';
					$params->isattach=0;
			    }
			}
			
		}
		if($result)
		{
			if ($params->save()) 
			{
				$msg_array['status']='1';
				$msg_array['info']=trans('admin.website_del_success');
				$msg_array['is_reload']=1;
				$msg_array['curl']='';
				$msg_array['resource']='';
				$msg_array['param_way']='';
				$msg_array['param_keyword']='';
			} 
			else 
			{
				$msg_array['status']='0';
				$msg_array['info']=trans('admin.website_del_failure');
				$msg_array['is_reload']=0;
				$msg_array['curl']='';
				$msg_array['resource']="";
				$msg_array['param_way']='';
				$msg_array['param_keyword']='';	
			}
		}
		else
		{
			$msg_array['status']='0';
			$msg_array['info']=trans('admin.website_del_failure');
			$msg_array['is_reload']=0;
			$msg_array['curl']='';
			$msg_array['resource']="";
			$msg_array['param_way']='';
			$msg_array['param_keyword']='';
		}
		
		return response()->json($msg_array);
	}
}
