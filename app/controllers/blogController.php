<?php 
/**
* 
*/
class blogController extends InitController
{

	function __construct() {
		$this->blogModel = new blogModel();
		$this->validate = new ValidateLib();
		$this->userModel = new userModel();
	}
	public function manager($req, $res) {
		$pagination = new PaginationLib();
		$data['seo']['title'] = "Quản lý Blogs";
		$data['name'] = "Quản lý blogs";
		$data['csrf_token'] = $req->csrfToken;
		$data['globals'] = $req->globals;

		$catPage = (Input::get('catPage')) ? Input::get('catPage') : 1;
		$postPage = (Input::get('postPage')) ? Input::get('postPage') : 1;
		$sobaihienthi = 10;

		$data['blog']['dsCat'] = $this->blogModel->select('id, name, parent, uid, time_created, status')->whereAnd(array('type' => 'cat'))->limit(($catPage - 1) * $sobaihienthi, $sobaihienthi)->order_by('id','DESC')->get()->toArray();
		$data['blog']['dsPost'] = $this->blogModel->select('id, name, parent, uid, time_created, status')->whereAnd(array('type' => 'post'))->limit(($postPage - 1) * $sobaihienthi, $sobaihienthi)->order_by('id','DESC')->get()->toArray();
		foreach ($data['blog']['dsPost'] as $k => $v) {
			$this->userModel->find($v['uid']);
			$cat = $this->blogModel->select('name')->whereAnd(array('type' => 'cat', 'id' => $v['parent']))->get()->rowArray();
			$data['blog']['dsPost'][$k]['nickname'] = $this->userModel->nickname;
			$data['blog']['dsPost'][$k]['parentName'] = $cat['name'];
		}
		foreach ($data['blog']['dsCat'] as $k => $v) {
			$this->userModel->find($v['uid']);
			$cat = $this->blogModel->select('name')->whereAnd(array('type' => 'cat', 'id' => $v['parent']))->get()->rowArray();
			$data['blog']['dsCat'][$k]['nickname'] = $this->userModel->nickname;
			$data['blog']['dsCat'][$k]['parentName'] = $cat['name'];
		}

		$data['blog']['countAllCat']	= $this->blogModel->select()->whereAnd(array('type' => 'cat'))->get()->countAll();
		$data['blog']['countAllPost']	= $this->blogModel->select()->whereAnd(array('type' => 'post'))->get()->countAll();
		$data['validate']['errors'] = isset($req->errors) ? $req->errors : null;
		//=================PAGINATION====================
	    $pagination->next_tag_html = '<button name="catPage" value="{$id}" class="pagination-btn btn btn-default"><i class="fa fa-chevron-right"></i></button>';
	    $pagination->next_tag_disabled_html = '<button disabled name="catPage" value="{$id}" class="btn btn-default"><i class="fa fa-chevron-right"></i></button>';
	    $pagination->prev_tag_html = '<button name="catPage" value="{$id}" class="pagination-btn btn btn-default"><i class="fa fa-chevron-left"></i></button>';
	    $pagination->prev_tag_disabled_html = '<button disabled name="catPage" value="{$id}" class="btn btn-default"><i class="fa fa-chevron-left"></i></button>';
	    $pagination->num_tag_html = '<button name="catPage" value="{$id}" class="pagination-btn  btn btn-default">{$id}</button>';
	    $pagination->cur_tag_html = '<button disabled name="catPage" value="{$id}" class="btn btn-default active">{$id}</button>';
		$data['paginationCat'] = $pagination->html($data['blog']['countAllCat'],$catPage,$sobaihienthi);
	    $pagination->next_tag_html = '<button name="postPage" value="{$id}" class="pagination-btn btn btn-default"><i class="fa fa-chevron-right"></i></button>';
	    $pagination->next_tag_disabled_html = '<button disabled name="postPage" value="{$id}" class="btn btn-default"><i class="fa fa-chevron-right"></i></button>';
	    $pagination->prev_tag_html = '<button name="postPage" value="{$id}" class="pagination-btn btn btn-default"><i class="fa fa-chevron-left"></i></button>';
	    $pagination->prev_tag_disabled_html = '<button disabled name="postPage" value="{$id}" class="btn btn-default"><i class="fa fa-chevron-left"></i></button>';
	    $pagination->num_tag_html = '<button name="postPage" value="{$id}" class="pagination-btn btn btn-default">{$id}</button>';
	    $pagination->cur_tag_html = '<button disabled name="postPage" value="{$id}" class="btn btn-default active">{$id}</button>';
		$data['paginationPost'] = $pagination->html($data['blog']['countAllPost'],$postPage,$sobaihienthi);
		//=================/PAGINATION====================
		return $res->render("admin-flat/blog/manager", "admin-flat/layout/admin.layout", $data);
	}

	public function create($req, $res) {
		if (Input::get('type')!= 'cat' && Input::get('type') != 'post') {
			return $res->redirect('/admin/blog');
		}

		$data['type'] = Input::get('type');
		
		if (Input::get('type') == 'cat') {
			$data['seo']['title'] = $data['name'] = "Thêm thư mục";
		} else {
			$data['seo']['title'] = $data['name'] = "Thêm bài viết";
		}
		
		$data['csrf_token'] = $req->csrfToken;
		$data['globals'] = $req->globals;
		$data['validate']['errors'] = isset($req->errors) ? $req->errors : null;
		$data['validate']['success'] = isset($req->success) ? $req->success : null;
		//=============================
		$data['dsCatParentRoot'] = $this->blogModel->select('id, name, parent')->whereAnd(array('type' => 'cat','parent' => 0))->get()->toArray();
		return $res->render("admin-flat/blog/createCat", "admin-flat/layout/admin.layout", $data);
	}

	public function createPost($req, $res) {
		if ($req->csrf) {
			$this->validate->checkBody('name','Tên không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('title','Tiêu đề không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('content','Nội dung không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('parent','Vui lòng chọn thư mục!')->notEmpty();
			$this->validate->checkBody('parent','Thư mục phải là số!')->notNumeric();
			if ($this->validate->errors) {
				return $res->redirect('/admin/blog/create?type=' . Input::post('type'))->with(array('errors' => $this->validate->errors));
			} else {
				$this->blogModel->name = Input::post('name');
				$this->blogModel->title = Input::post('title');
				$this->blogModel->content = Input::post('content');
				$this->blogModel->parent = Input::post('parent');

				$this->blogModel->uid = $req->globals['infoUser']['id'];
				if (Input::post('btnOk') == 'publish') {
					$this->blogModel->status = 1;
				} else {
					$this->blogModel->status = 0;
				}
				$this->blogModel->view = 0;
				$this->blogModel->attach = 0;
				$this->blogModel->type = Input::post('type');

				$this->blogModel->des = Input::post('des');
				$this->blogModel->keyword = Input::post('keyword');
				$this->blogModel->time_created = date("Y-m-d H:i:s",time());
				$this->blogModel->save();
				return $res->redirect('/admin/blog/create?type=' . Input::post('type'))->with(array('success' => "Thêm thành công!"));
			}
		} else {
			die("Sai ToKen!");
		}
	}

	public function edit($req, $res) {
		if (Input::get('type') != 'cat' && Input::get('type') != 'post') {
			return $res->redirect('/admin/blog');
		} else if (!Input::get('id')) {
			return $res->redirect('/admin/blog');
		} else if(!$this->blogModel->find(Input::get('id'))) {
			return $res->redirect('/admin/blog');
		} else if(!$this->blogModel->whereAnd(array('type' => Input::get('type'), 'id' => Input::get('id')))->get()->countAll()) {
			return $res->redirect('/admin/blog');
		}
		$data['infoBlog'] = $this->blogModel->whereAnd(array('type' => Input::get('type'), 'id' => Input::get('id')))->get()->rowArray();
		$data['type'] = Input::get('type');
		$data['seo']['title'] = "Chỉnh sửa blogs";
		if ($data['type'] == 'cat') {
			$data['name'] = "Chỉnh sửa thư mục";
		} else {
			$data['name'] = "Chỉnh sửa bài viết";
		}
		
		$data['csrf_token'] = $req->csrfToken;
		$data['globals'] = $req->globals;
		$data['validate']['errors'] = isset($req->errors) ? $req->errors : null;
		$data['validate']['success'] = isset($req->success) ? $req->success : null;
		//=============================
		$data['dsCatParentRoot'] = $this->blogModel->select('id, name, parent')->whereAnd(array('type' => 'cat','parent' => 0))->get()->toArray();
		return $res->render("admin-flat/blog/edit", "admin-flat/layout/admin.layout", $data);

	}

	public function editPost($req, $res) {
		if ($req->csrf) {
			$this->validate->checkBody('name','Tên không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('title','Tiêu đề không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('content','Nội dung không được bỏ trống!')->notEmpty();
			$this->validate->checkBody('parent','Vui lòng chọn thư mục!')->notEmpty();
			$this->validate->checkBody('parent','Thư mục phải là số!')->notNumeric();
			$this->validate->checkBody('id','Id phải là số!')->notNumeric();
			$this->validate->checkBody('id','Id không được bỏ trống!')->notEmpty();
			if (!Input::post('id') || !Input::post('type')) {
				array_push($this->validate->errors, "Không tại ID hoặc Type");
			}
			$id = Input::post('id');
			if (!$this->blogModel->find($id)) {
				array_push($this->validate->errors, "ID không tại trong hệ thống!");
			}
			if ($this->validate->errors) {
				return $res->redirect('/admin/blog/edit?type=' . Input::post('type') . '&id=' . Input::post('id'))->with(array('errors' => $this->validate->errors));
			} else {
				$this->blogModel->name = Input::post('name');
				$this->blogModel->title = Input::post('title');
				$this->blogModel->content = Input::post('content');
				$this->blogModel->parent = Input::post('parent');

				$this->blogModel->uid = $req->globals['infoUser']['id'];
				if (Input::post('btnOk') == 'publish') {
					$this->blogModel->status = 1;
				} else {
					$this->blogModel->status = 0;
				}
				$this->blogModel->view = 0;
				$this->blogModel->attach = 0;
				$this->blogModel->type = Input::post('type');

				$this->blogModel->des = Input::post('des');
				$this->blogModel->keyword = Input::post('keyword');
				$this->blogModel->time_updated = date("Y-m-d H:i:s",time());
				$this->blogModel->save();
				if ($this->blogModel->save()) {
					return $res->redirect('/admin/blog/edit?type=' . Input::post('type') . '&id=' . Input::post('id'))->with(array('success' => "Chỉnh sửa thành công!"));
				} else {
					return $res->redirect('/admin/blog/edit?type=' . Input::post('type') . '&id=' . Input::post('id'))->with(array('errors' => array("Có lỗi xảy ra!")));
				}
				
			}
		} else {
			die("Sai ToKen!");
		}
	}

}