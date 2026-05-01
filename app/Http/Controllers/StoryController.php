<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StoryController extends Controller
{
    public function index()
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $currentUserId = auth()->id();
            
            // Get active stories from other users (not current user)
            $stories = Story::with('user')
                ->active()
                ->fromOthers($currentUserId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get current user's active story
            $currentUserStory = Story::where('user_id', $currentUserId)
                ->active()
                ->first();

            return response()->json([
                'stories' => $stories,
                'currentUserStory' => $currentUserStory
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $request->validate([
                'content' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Check if user already has an active story
            $existingStory = Story::where('user_id', auth()->id())
                ->active()
                ->first();

            if ($existingStory) {
                return response()->json(['error' => 'You already have an active story'], 400);
            }

            $storyData = [
                'user_id' => auth()->id(),
                'content' => $request->content,
                'media_type' => 'text',
                'expires_at' => now()->addHours(24), // Stories expire after 24 hours
            ];

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                
                // Create storage directory if it doesn't exist
                $storagePath = storage_path('app/public/stories/images');
                if (!is_dir($storagePath)) {
                    mkdir($storagePath, 0755, true);
                }
                
                $path = $image->store('stories/images', 'public');
                
                if (!$path) {
                    return response()->json(['error' => 'Failed to store image'], 500);
                }
                
                $storyData['image_path'] = $path;
                $storyData['image_name'] = $image->getClientOriginalName();
                $storyData['media_type'] = 'image';
            }

            $story = Story::create($storyData);

            // Load the story with user relationship
            $story->load('user');

            return response()->json($story);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create story: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $story = Story::find($id);
            
            if (!$story) {
                return response()->json(['error' => 'Story not found'], 404);
            }

            // Check if user owns the story
            if ($story->user_id != auth()->id()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            // Delete image file if exists
            if ($story->image_path) {
                Storage::disk('public')->delete($story->image_path);
            }

            $story->delete();

            return response()->json(['success' => 'Story deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete story'], 500);
        }
    }

    // Clean up expired stories (can be called by a scheduler)
    public function cleanupExpired()
    {
        try {
            $expiredStories = Story::where('expires_at', '<', now())->get();
            
            foreach ($expiredStories as $story) {
                // Delete image file if exists
                if ($story->image_path) {
                    Storage::disk('public')->delete($story->image_path);
                }
                $story->delete();
            }

            return response()->json(['success' => 'Cleaned up ' . $expiredStories->count() . ' expired stories']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cleanup expired stories'], 500);
        }
    }
}
