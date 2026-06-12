<?php
/**
 * master/diamond/repository.php
 * Repository Diamond — query database saja
 */
declare(strict_types=1);

class DiamondRepository
{
    public static function getList(
        string $search='', string $status='', string $acqType='',
        int $supplierId=0, int $warehouseId=0,
        string $sortBy='d.created_at', string $sortDir='DESC',
        int $limit=DEFAULT_PER_PAGE, int $offset=0
    ): array {
        $where=['d.deleted_at IS NULL']; $params=[];
        if ($search!=='') {
            $like='%'.sanitize_like($search).'%';
            $where[]='(d.internal_code LIKE ? OR d.factory_barcode LIKE ? OR d.color_grade LIKE ? OR d.clarity_grade LIKE ?)';
            $params=array_merge($params,[$like,$like,$like,$like]);
        }
        if ($status!=='')    { $where[]='d.status=?';           $params[]=$status; }
        if ($acqType!=='')   { $where[]='d.acquisition_type=?'; $params[]=$acqType; }
        if ($supplierId>0)   { $where[]='d.supplier_id=?';      $params[]=$supplierId; }
        if ($warehouseId>0)  { $where[]='d.warehouse_id=?';     $params[]=$warehouseId; }

        $whereStr=implode(' AND ',$where);
        $allowSort=['d.internal_code','d.carat_weight','d.selling_price_usd','d.acquired_at','d.status','d.created_at'];
        if (!in_array($sortBy,$allowSort,true)) $sortBy='d.created_at';
        $sortDir=strtoupper($sortDir)==='ASC'?'ASC':'DESC';
        $params[]=$limit; $params[]=$offset;

        return Database::fetchAll(
            "SELECT d.*,
                    s.name AS supplier_name, s.supplier_code,
                    w.name AS warehouse_name, w.warehouse_code,
                    b.name AS branch_name,
                    ds.name AS shape_name,
                    dc.cert_number, dc.cert_type
               FROM diamonds d
               LEFT JOIN suppliers s ON s.id = d.supplier_id
               LEFT JOIN warehouses w ON w.id = d.warehouse_id
               LEFT JOIN branches b ON b.id = w.branch_id
               LEFT JOIN diamond_shapes ds ON ds.id = d.shape_id
               LEFT JOIN diamond_certificates dc ON dc.diamond_id = d.id AND dc.is_primary = 1
              WHERE {$whereStr}
              ORDER BY {$sortBy} {$sortDir}
              LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countList(
        string $search='', string $status='', string $acqType='',
        int $supplierId=0, int $warehouseId=0
    ): int {
        $where=['d.deleted_at IS NULL']; $params=[];
        if ($search!=='') {
            $like='%'.sanitize_like($search).'%';
            $where[]='(d.internal_code LIKE ? OR d.factory_barcode LIKE ? OR d.color_grade LIKE ? OR d.clarity_grade LIKE ?)';
            $params=array_merge($params,[$like,$like,$like,$like]);
        }
        if ($status!=='')   { $where[]='d.status=?';           $params[]=$status; }
        if ($acqType!=='')  { $where[]='d.acquisition_type=?'; $params[]=$acqType; }
        if ($supplierId>0)  { $where[]='d.supplier_id=?';      $params[]=$supplierId; }
        if ($warehouseId>0) { $where[]='d.warehouse_id=?';     $params[]=$warehouseId; }
        $row=Database::fetchOne(
            "SELECT COUNT(*) n FROM diamonds d WHERE ".implode(' AND ',$where),$params
        );
        return (int)($row['n']??0);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT d.*,
                    s.name AS supplier_name, s.supplier_code, s.currency AS supplier_currency,
                    s.discount_percent AS supplier_discount,
                    w.name AS warehouse_name, w.warehouse_code, w.type AS warehouse_type,
                    b.name AS branch_name, b.branch_code,
                    ds.name AS shape_name, ds.code AS shape_code
               FROM diamonds d
               LEFT JOIN suppliers s ON s.id = d.supplier_id
               LEFT JOIN warehouses w ON w.id = d.warehouse_id
               LEFT JOIN branches b ON b.id = w.branch_id
               LEFT JOIN diamond_shapes ds ON ds.id = d.shape_id
              WHERE d.id=? AND d.deleted_at IS NULL",
            [$id]
        );
    }

    public static function findByCode(string $code): ?array
    {
        return Database::fetchOne(
            "SELECT d.*, s.name AS supplier_name, w.name AS warehouse_name
               FROM diamonds d
               LEFT JOIN suppliers s ON s.id=d.supplier_id
               LEFT JOIN warehouses w ON w.id=d.warehouse_id
              WHERE (d.internal_code=? OR d.factory_barcode=?) AND d.deleted_at IS NULL",
            [$code,$code]
        );
    }

    public static function generateInternalCode(): string
    {
        $year  = date('Y');
        $last  = Database::fetchOne(
            "SELECT internal_code FROM diamonds
              WHERE internal_code LIKE ? ORDER BY id DESC LIMIT 1
              FOR UPDATE",
            ["OO-{$year}-%"]
        );
        $seq = $last ? ((int)substr($last['internal_code'],-5))+1 : 1;
        return sprintf('OO-%s-%05d', $year, $seq);
    }

    public static function create(array $data): int
    {
        return (int)Database::insert(
            "INSERT INTO diamonds
               (internal_code,factory_barcode,supplier_id,warehouse_id,
                acquisition_type,acquired_at,shape_id,
                carat_weight,color_grade,clarity_grade,cut_grade,
                polish,symmetry,fluorescence,measurements,
                table_percent,depth_percent,stone_count,
                metal_type,metal_weight_gr,karat,
                cost_price_usd,selling_price_usd,
                status,notes,registered_by,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'registered',?,?,?)",
            [
                $data['internal_code'],
                $data['factory_barcode']?:null,
                (int)$data['supplier_id'],
                (int)$data['warehouse_id'],
                $data['acquisition_type'],
                $data['acquired_at'],
                $data['shape_id']?:null,
                (float)$data['carat_weight'],
                $data['color_grade']?:null,
                $data['clarity_grade']?:null,
                $data['cut_grade']?:null,
                $data['polish']?:null,
                $data['symmetry']?:null,
                $data['fluorescence']?:null,
                $data['measurements']?:null,
                $data['table_percent']!==''?(float)$data['table_percent']:null,
                $data['depth_percent']!==''?(float)$data['depth_percent']:null,
                (int)($data['stone_count']??1),
                $data['metal_type']?:null,
                $data['metal_weight_gr']!==''?(float)$data['metal_weight_gr']:null,
                $data['karat']?:null,
                (float)$data['cost_price_usd'],
                (float)$data['selling_price_usd'],
                $data['notes']?:null,
                $_SESSION['user_id']??null,
                $_SESSION['user_id']??null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            "UPDATE diamonds SET
               factory_barcode=?,supplier_id=?,warehouse_id=?,
               acquisition_type=?,acquired_at=?,shape_id=?,
               carat_weight=?,color_grade=?,clarity_grade=?,cut_grade=?,
               polish=?,symmetry=?,fluorescence=?,measurements=?,
               table_percent=?,depth_percent=?,stone_count=?,
               metal_type=?,metal_weight_gr=?,karat=?,
               cost_price_usd=?,selling_price_usd=?,notes=?,updated_by=?
             WHERE id=?",
            [
                $data['factory_barcode']?:null,
                (int)$data['supplier_id'],(int)$data['warehouse_id'],
                $data['acquisition_type'],$data['acquired_at'],
                $data['shape_id']?:null,(float)$data['carat_weight'],
                $data['color_grade']?:null,$data['clarity_grade']?:null,
                $data['cut_grade']?:null,$data['polish']?:null,
                $data['symmetry']?:null,$data['fluorescence']?:null,
                $data['measurements']?:null,
                $data['table_percent']!==''?(float)$data['table_percent']:null,
                $data['depth_percent']!==''?(float)$data['depth_percent']:null,
                (int)($data['stone_count']??1),
                $data['metal_type']?:null,
                $data['metal_weight_gr']!==''?(float)$data['metal_weight_gr']:null,
                $data['karat']?:null,
                (float)$data['cost_price_usd'],(float)$data['selling_price_usd'],
                $data['notes']?:null,$_SESSION['user_id']??null,$id,
            ]
        );
    }

    public static function updateStatus(int $id, string $status, string $eventName,
        ?string $refType=null, ?int $refId=null, ?string $notes=null): void
    {
        $old = Database::fetchOne("SELECT status FROM diamonds WHERE id=?",[$id]);
        Database::query("UPDATE diamonds SET status=?,updated_by=? WHERE id=?",
            [$status,$_SESSION['user_id']??null,$id]);
        // Catat riwayat
        Database::query(
            "INSERT INTO diamond_state_histories
               (diamond_id,from_status,to_status,event_name,ref_type,ref_id,actor_id,notes)
             VALUES (?,?,?,?,?,?,?,?)",
            [$id,$old['status']??null,$status,$eventName,$refType,$refId,
             $_SESSION['user_id']??null,$notes]
        );
    }

    public static function softDelete(int $id): void
    {
        Database::query("UPDATE diamonds SET deleted_at=NOW(),status='retired',updated_by=? WHERE id=?",
            [$_SESSION['user_id']??null,$id]);
    }

    public static function getStats(): array
    {
        return Database::fetchOne(
            "SELECT COUNT(*) total,
               SUM(status='available') available,
               SUM(status='reserved') reserved,
               SUM(status='sold') sold,
               SUM(status='registered') registered,
               SUM(status='in_repair') in_repair,
               SUM(acquisition_type='consignment') consignment
             FROM diamonds WHERE deleted_at IS NULL"
        )??[];
    }

    public static function getShapes(): array
    {
        return Database::fetchAll("SELECT * FROM diamond_shapes WHERE is_active=1 ORDER BY name");
    }

    public static function getCertificate(int $diamondId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM diamond_certificates WHERE diamond_id=? AND is_primary=1",[$diamondId]
        );
    }

    public static function saveCertificate(int $diamondId, array $data): void
    {
        $existing=Database::fetchOne(
            "SELECT id FROM diamond_certificates WHERE diamond_id=? AND is_primary=1",[$diamondId]
        );
        if ($existing) {
            Database::query(
                "UPDATE diamond_certificates SET cert_number=?,cert_type=?,issuer=?,issue_date=? WHERE id=?",
                [$data['cert_number'],$data['cert_type'],$data['issuer']?:null,
                 $data['issue_date']?:null,$existing['id']]
            );
        } else {
            Database::query(
                "INSERT INTO diamond_certificates (diamond_id,cert_number,cert_type,issuer,issue_date,is_primary) VALUES (?,?,?,?,?,1)",
                [$diamondId,$data['cert_number'],$data['cert_type'],
                 $data['issuer']?:null,$data['issue_date']?:null]
            );
        }
    }

    public static function getStateHistories(int $diamondId): array
    {
        return Database::fetchAll(
            "SELECT h.*, u.full_name AS actor_name
               FROM diamond_state_histories h
               LEFT JOIN users u ON u.id=h.actor_id
              WHERE h.diamond_id=? ORDER BY h.changed_at DESC",
            [$diamondId]
        );
    }

    /** Hitung harga jual dalam IDR menggunakan kurs aktif */
    public static function getActiveRate(): float
    {
        $row=Database::fetchOne(
            "SELECT rate_to_idr FROM currencies
              WHERE code='USD' AND is_active=1
                AND effective_date <= CURDATE()
              ORDER BY effective_date DESC LIMIT 1"
        );
        return (float)($row['rate_to_idr']??16000);
    }
}
