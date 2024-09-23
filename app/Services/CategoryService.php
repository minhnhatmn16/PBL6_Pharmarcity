<?php
namespace App\Services;

use App\Http\Requests\RequestDeleteCategory;
use App\Http\Requests\RequestDeleteManyCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Repositories\CategoryInterface;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Termwind\Components\BreakLine;
use Throwable;

class CategoryService{
    use APIResponse;
    protected CategoryInterface $categoryRepository;
    public function __construct(CategoryInterface $categoryRepository){
        $this->categoryRepository = $categoryRepository;
    }
    public function add($request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            if($request->hasFile('category_thumbnail')){
                $image = $request->file('category_thumbnail');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/category_thumbnail',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                $data['category_thumbnail'] = $url;
            }
            $category = Category::create($data);
            DB::commit();
            return $this->responseSuccessWithData($category, 'Thêm category mới thành công!', 201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function update(RequestUpdateCategory $request,$id){
        DB::beginTransaction();
        try{
           $category = Category::where("category_id",$id)->first();
           if(empty($category)){
               return $this->responseError("Category không tồn tại trong hệ thống",404);
           }
           if($request->hasFile('category_thumbnail')){
               if($category->category_thumbnail){
                    $id_file = explode('.', implode('/', array_slice(explode('/', $category->category_thumbnail), 7)))[0];
                    Cloudinary::destroy($id_file);
               }
               $image = $request->file('category_thumbnail');
               $uploadFile = Cloudinary::upload($image->getRealPath(), [
                   'folder' => 'pbl6_pharmacity/thumbnail/category_thumbnail',
                   'resource_type' => 'auto'
               ]);
               $url = $uploadFile->getSecurePath();
               $data = array_merge($request->all(),['category_thumbnail'=>$url]);
               $category->update($data);
           }
           else{
                $request['category_thumbnail'] = $category->category_thumbnail;
                $category->update($request->all());
           }
            DB::commit();
            return $this->responseSuccessWithData($category, 'Cập nhật category thành công!', 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function delete(RequestDeleteCategory $request, $id)
    {
        DB::beginTransaction();
        try {
            $category = Category::where("category_id", $id)->first();
            if (empty($category)) {
                return $this->responseError("Không tìm thấy category", 404);
            }
            $category->update(['category_is_delete'=> $request->category_is_delete]);
            DB::commit();
            $request->category_is_delete == 1 ? $message = "Xoá category thành công!" : $message = "Khôi phục category thành công!";
            return $this->responseSuccess($message ,200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function deleteMany(RequestDeleteManyCategory $request){
        DB::beginTransaction();
        try{
            $ids_category=$request->ids_category;
            $categories = CategoryRepository::getCategory(['ids_category'=> $ids_category])->get();
            if($categories->isEmpty()){
                return $this->responseError("Không tìm thấy category",404);
            }
            foreach($categories as $index => $category){
                $category->update(['category_is_delete'=>$request->category_is_delete]);
            }
            DB::commit();
            $request->category_is_delete == 1 ? $message = "Xoá các category thành công!" : $message = "Khôi phục các category thành công!";
            return $this->responseSuccess($message,200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function get(Request $request,$id){
        try{
            $category = Category::where("category_id", $id)->first();
            if(empty($category)){
                return $this->responseError("Không tìm thấy category",404);
            }
            $categories = Category::where("category_parent_id", $id)->get();
            if($categories->isEmpty()){
                return $this->responseSuccessWithData($category, "Lấy thông tin category thành công!",200);
            }
            return $this->responseSuccessWithData($categories, "Lấy danh sách con của category thành công!",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function getAll(Request $request){
        try{
            $orderBy = $request->typesort ?? 'category_id';
            switch($orderBy){
                case 'category_name':
                    $orderBy = 'category_name';
                    break;
                case 'category_type':
                    $orderBy = 'category_type';
                    break;
                case 'category_parent_id':
                    $orderBy = 'category_parent_id';
                    break;
                case 'category_id':
                    $orderBy = 'category_id';
                    break;
                default:
                    $orderBy = 'category_id';
                    break;
            }
            $orderDirection = $request->sortlatest ?? 'true';
            switch($orderDirection){
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter = (object) [
                'search' => $request->search ?? '',
                'category_is_delete' => $request->category_is_delete ?? 'all',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
            $categories = CategoryRepository::getAll($filter);
            if(!(empty($request->paginate))){
                $categories = $categories->paginate($request->paginate);
            }
            else{
                $categories = $categories->get();
            }
            return $this->responseSuccessWithData($categories, "Lấy danh sách category thành công!",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
}