<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
  {
    try {
        $books = Book::all(); // Fetch all books
        return response()->json($books, 200); // Return books as JSON with a 200 status
    } catch (\Exception $e) {
        Log::error('Failed to retrieve books: ' . $e->getMessage()); // Log the error
        return response()->json(['error' => 'Unable to retrieve books'], 500); // Return an error response
    }
  }
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'string',
            'author' => 'required|string',
            'isbn' => 'string|unique:books',
            'published_date' => 'string',
            'cover_image' => 'nullable|image|max:2048'
        ]);

        try {
            $published_date = Carbon::parse($data['published_date'])->toDateString();
            $data['published_date'] = $published_date;
            $data['user_id'] = 1; // Making the assumption the id of the user is 1 
        } catch (\Exception $e) {
            return response()->json(['Error' => 'Bad Request'], 400);
        }

        $book = new Book($data);

        if ($request->hasFile('cover_image')) {
            $coverImage = $request->file('cover_image');
            $coverImageName = time() . '.' . $coverImage->getClientOriginalExtension();
            $coverImage->move(public_path('images'), $coverImageName);
            $book->cover_image = $coverImageName;
        }
        $book->save();
        return response()->json(['message' => 'Book added successfully'], 201);
    }



   
    public function update(Request $request, $id)
{
    // Find the book or fail
    $book = Book::findOrFail($id);

    // Validate the incoming request data
    $data = $request->validate([
        'title' => 'required|string',  // Title must be a string and required
        'author' => 'required|string',  // Author must be a string and required
        'isbn' => 'string|unique:books,isbn,' . $id,  // ISBN must be unique except for the current book
        'published_date' => 'string|date',  // Ensure published_date is a valid date string
        'cover_image' => 'nullable|image|max:2048'  // Cover image validation
    ]);

    try {
        // Parse and format published date
        $published_date = Carbon::parse($data['published_date'])->toDateString();
        $data['published_date'] = $published_date;
        $data['user_id'] = $book->user_id;  // Retain the user ID from the existing book
    } catch (\Throwable $th) {
        Log::error('Error parsing date: ' . $th->getMessage());
        return response()->json(['Error' => 'Bad Request'], 400);
    }

    // Update the book with validated data
    $book->update($data);

    // Handle cover image upload if provided
    if ($request->hasFile('cover_image')) {
        // Delete the previous cover image if it exists
        if ($book->cover_image) {
            Storage::delete('public/images/' . $book->cover_image);
        }
        $coverImage = $request->file('cover_image');
        $coverImageName = time() . '.' . $coverImage->getClientOriginalExtension();
        $coverImage->storeAs('public/images', $coverImageName);
        $book->cover_image = $coverImageName;
        $book->save();
    }

    return response()->json(['message' => 'Book updated successfully'], 200);
}

    public function destroy($id)
    {
        $book = Book::findOrFail($id);

        // Delete the cover image if it exists
        if ($book->cover_image) {
            Storage::delete('public/images/' . $book->cover_image);
        }

        $book->delete();

        return response()->json(['message' => 'Book deleted successfully'], 200);
    }
}
