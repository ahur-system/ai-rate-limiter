<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
{
    protected $format = 'json';
    
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        // This endpoint is protected by AI Rate Limiting
        // The filter will automatically check rate limits before this method is called
        
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];
        
        return $this->respond([
            'status' => 'success',
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }
    
    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        $user = [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => '2024-01-15 10:00:00'
        ];
        
        return $this->respond([
            'status' => 'success',
            'data' => $user,
            'message' => 'User retrieved successfully'
        ]);
    }
    
    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        return $this->respond([
            'status' => 'success',
            'message' => 'New user form'
        ]);
    }
    
    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        $data = $this->request->getJSON();
        
        // Simulate user creation
        $user = [
            'id' => 4,
            'name' => $data->name ?? 'New User',
            'email' => $data->email ?? 'newuser@example.com',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->respondCreated([
            'status' => 'success',
            'data' => $user,
            'message' => 'User created successfully'
        ]);
    }
    
    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        return $this->respond([
            'status' => 'success',
            'message' => 'Edit user form for ID: ' . $id
        ]);
    }
    
    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        $data = $this->request->getJSON();
        
        $user = [
            'id' => $id,
            'name' => $data->name ?? 'Updated User',
            'email' => $data->email ?? 'updated@example.com',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->respond([
            'status' => 'success',
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }
    
    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        return $this->respond([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
} 