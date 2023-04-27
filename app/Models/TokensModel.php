<?php

namespace App\Models;

use CodeIgniter\Model;

class TokensModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tokens';
    protected $primaryKey       = 'token_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'admin_id',
        'token',
        'created_at',
        'expires_at',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];


    public function createToken($userId, $token, $expiresAt)
    {
        // Check to see if a particular id already exists in the tokens db
        // if it does, then you would uptae the token and expiry on that id
        // Else do a new insert
        // This is to prevent multiple id entries
        $data = [
            'admin_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ];

        return $this->insert($data);
    }

    public function updateToken($tokenId, $newToken, $newExpiresAt)
    {
        $data = [
            'token' => $newToken,
            'expires_at' => $newExpiresAt,
        ];

        return $this->update($tokenId, $data);
    }

    public function deleteToken($tokenId)
    {
        return $this->delete($tokenId);
    }
}
