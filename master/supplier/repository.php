<?php
/**
 * master/supplier/repository.php
 */
declare(strict_types=1);

class SupplierRepository
{
    public static function getList(string $search='', string $type='', int $isActive=-1,
        string $sortBy='s.name', string $sortDir='ASC', int $limit=DEFAULT_PER_PAGE, int $offset=0): array
    {
        $where=['s.deleted_at IS NULL']; $params=[];
        if ($search!=='') {
            $like='%'.sanitize_like($search).'%';
            $where[]='(s.name LIKE ? OR s.supplier_code LIKE ? OR s.phone LIKE ? OR s.contact_person LIKE ?)';
            $params=array_merge($params,[$like,$like,$like,$like]);
        }
        if ($type!=='')    { $where[]='s.type=?';      $params[]=$type; }
        if ($isActive>=0)  { $where[]='s.is_active=?'; $params[]=$isActive; }
        $whereStr=implode(' AND ',$where);
        $allowSort=['s.name','s.supplier_code','s.type','s.currency','s.created_at'];
        if (!in_array($sortBy,$allowSort,true)) $sortBy='s.name';
        $sortDir=strtoupper($sortDir)==='DESC'?'DESC':'ASC';
        $params[]=$limit; $params[]=$offset;
        return Database::fetchAll(
            "SELECT s.* FROM suppliers s WHERE {$whereStr} ORDER BY {$sortBy} {$sortDir} LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countList(string $search='', string $type='', int $isActive=-1): int
    {
        $where=['deleted_at IS NULL']; $params=[];
        if ($search!=='') {
            $like='%'.sanitize_like($search).'%';
            $where[]='(name LIKE ? OR supplier_code LIKE ? OR phone LIKE ? OR contact_person LIKE ?)';
            $params=array_merge($params,[$like,$like,$like,$like]);
        }
        if ($type!=='')   { $where[]='type=?';      $params[]=$type; }
        if ($isActive>=0) { $where[]='is_active=?'; $params[]=$isActive; }
        $row=Database::fetchOne("SELECT COUNT(*) n FROM suppliers WHERE ".implode(' AND ',$where),$params);
        return (int)($row['n']??0);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM suppliers WHERE id=? AND deleted_at IS NULL",[$id]
        );
    }

    public static function isCodeTaken(string $code, int $excludeId=0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM suppliers WHERE supplier_code=? AND id!=? AND deleted_at IS NULL",
            [$code,$excludeId]
        )!==null;
    }

    public static function getNextCode(): string
    {
        $last=Database::fetchOne("SELECT supplier_code FROM suppliers ORDER BY id DESC LIMIT 1");
        if (!$last) return 'SUP-001';
        preg_match('/(\d+)$/',$last['supplier_code'],$m);
        $num=(int)($m[1]??0)+1;
        return 'SUP-'.str_pad((string)$num,3,'0',STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        return (int)Database::insert(
            "INSERT INTO suppliers
               (supplier_code,name,contact_person,phone,phone2,email,address,
                type,currency,discount_percent,payment_terms,
                bank_name,bank_account,bank_holder,notes,is_active,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?)",
            [
                $data['supplier_code'],$data['name'],
                $data['contact_person']?:null,$data['phone']?:null,$data['phone2']?:null,
                $data['email']?:null,$data['address']?:null,
                $data['type'],$data['currency'],
                (float)($data['discount_percent']??0),
                $data['payment_terms']?:null,
                $data['bank_name']?:null,$data['bank_account']?:null,$data['bank_holder']?:null,
                $data['notes']?:null,
                $_SESSION['user_id']??null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE suppliers SET
               name=?,contact_person=?,phone=?,phone2=?,email=?,address=?,
               type=?,currency=?,discount_percent=?,payment_terms=?,
               bank_name=?,bank_account=?,bank_holder=?,notes=?,is_active=?,updated_by=?
             WHERE id=?",
            [
                $data['name'],$data['contact_person']?:null,
                $data['phone']?:null,$data['phone2']?:null,
                $data['email']?:null,$data['address']?:null,
                $data['type'],$data['currency'],
                (float)($data['discount_percent']??0),
                $data['payment_terms']?:null,
                $data['bank_name']?:null,$data['bank_account']?:null,$data['bank_holder']?:null,
                $data['notes']?:null,
                isset($data['is_active'])?1:0,
                $_SESSION['user_id']??null,$id,
            ]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query(
            "UPDATE suppliers SET deleted_at=NOW(),is_active=0,updated_by=? WHERE id=?",
            [$_SESSION['user_id']??null,$id]
        );
    }

    public static function getDropdown(string $search='', string $type=''): array
    {
        $where=['deleted_at IS NULL','is_active=1']; $params=[];
        if ($search!=='') {
            $like='%'.sanitize_like($search).'%';
            $where[]='(name LIKE ? OR supplier_code LIKE ?)';
            $params=[$like,$like];
        }
        if ($type!=='') { $where[]='type IN (?,?)'; $params[]=[$type,'both']; }
        return Database::fetchAll(
            "SELECT id,supplier_code,name,type,currency,discount_percent
               FROM suppliers WHERE ".implode(' AND ',$where)." ORDER BY name LIMIT 50",
            $params
        );
    }

    public static function getStats(): array
    {
        return Database::fetchOne(
            "SELECT COUNT(*) total,
               SUM(is_active=1) active,
               SUM(type='consignment') consignment,
               SUM(type='purchase') purchase,
               SUM(type='both') both_type
             FROM suppliers WHERE deleted_at IS NULL"
        )??[];
    }
}

// =============================================================================
// SERVICE
// =============================================================================
class SupplierService
{
    public static function validate(array $data, int $supplierId=0): array
    {
        $errors=[];
        if (empty(trim($data['name']??''))) $errors['name']='Nama supplier wajib diisi.';
        if (!in_array($data['type']??'',['consignment','purchase','both'],true))
            $errors['type']='Jenis supplier wajib dipilih.';
        if (!in_array($data['currency']??'',['USD','IDR'],true))
            $errors['currency']='Mata uang wajib dipilih.';
        if (isset($data['discount_percent']) && ((float)$data['discount_percent']<0||(float)$data['discount_percent']>100))
            $errors['discount_percent']='Diskon harus antara 0-100%.';
        if (!empty($data['email'])&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))
            $errors['email']='Format email tidak valid.';
        return $errors;
    }

    public static function create(array $data): int
    {
        $errors=self::validate($data);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));
        return Database::transaction(function() use ($data) {
            $data['supplier_code']=SupplierRepository::getNextCode();
            $id=SupplierRepository::create($data);
            audit_log('SUPPLIER','CREATE',$data['supplier_code'],'suppliers',(string)$id,
                null,['name'=>$data['name'],'type'=>$data['type']],"Supplier baru: {$data['name']}"
            );
            return $id;
        });
    }

    public static function update(int $id, array $data): void
    {
        $existing=SupplierRepository::findById($id);
        if (!$existing) throw new RuntimeException('Supplier tidak ditemukan.');
        $errors=self::validate($data,$id);
        if (!empty($errors)) throw new RuntimeException(json_encode($errors));
        Database::transaction(function() use ($id,$data,$existing) {
            SupplierRepository::update($id,$data);
            audit_log('SUPPLIER','UPDATE',$existing['supplier_code'],'suppliers',(string)$id,
                ['name'=>$existing['name']],['name'=>$data['name']],
                "Supplier diperbarui: {$existing['supplier_code']}"
            );
        });
    }

    public static function delete(int $id): void
    {
        $existing=SupplierRepository::findById($id);
        if (!$existing) throw new RuntimeException('Supplier tidak ditemukan.');
        // Cek penggunaan di diamonds
        $inUse=Database::fetchOne(
            "SELECT COUNT(*) n FROM diamonds WHERE supplier_id=? AND deleted_at IS NULL",[$id]
        );
        if (($inUse['n']??0)>0)
            throw new RuntimeException('Supplier tidak dapat dihapus karena masih memiliki data berlian.');
        Database::transaction(function() use ($id,$existing) {
            SupplierRepository::softDelete($id);
            audit_log('SUPPLIER','DELETE',$existing['supplier_code'],'suppliers',(string)$id,
                ['name'=>$existing['name']],null,"Supplier dihapus: {$existing['supplier_code']}"
            );
        });
    }
}
