<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Axiom\Rules\ISBN;
use App\Models\Book;

class BookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        Validator::extend('check_title', function ($attribute, $value, $parameters, $validator) {
            

            $book=Book::where('title',request('title'))->exists();
                       
            if($book)
            {
              
              return FALSE;
            }
           
            return TRUE;               

      });
        

        return [
            
            'title'   => 'required|check_title|max:100',
            'author'  => 'nullable|max:100',
            'category_id'=>'required|exists:books_category,id',
            'book_code'=>'required|unique:books',
            'isbn_number'=>['nullable','unique:books',new ISBN],
            'quantity'=>'nullable|numeric',
            'cover_image'=>'nullable|mimes:jpg,jpeg,png',
           
        ];
    }

    public function messages()
    {
        return[
            
            'title.required'     =>  'Title is required',
            'title.check_title'   =>  'Already Exists',
            // 'author.required'     => 'Author is Required',
            'category_id.required'=>'Select Category',
            'book_code.required'=>'Book Code Required',
            // 'isbn_number.required'=>'Isbn Number Required',
            // 'quantity.required'=>'quantity Required',
            // 'cover_image.required'=>'Cover Image Required',
            'cover_image.mimes'=>'Choose jpg,jpeg,png file',
           
        ];
    }
}
