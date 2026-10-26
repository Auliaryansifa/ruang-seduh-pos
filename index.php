<?php
declare(strict_types=1);
require __DIR__.'/config.php';
$page=(string)($_GET['page'] ?? (current_user()?'dashboard':'login'));
$allowed=['login','dashboard','orders','new-order','receipt','reports','menu','users','logout'];
if(!in_array($page,$allowed,true)) $page='dashboard';
if($page==='logout') { if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',$p['secure'],$p['httponly']);}session_destroy();}header('Location: index.php?page=login');exit; }
if($page==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();$identity=mb_substr(trim((string)($_POST['identity']??'')),0,120);$password=(string)($_POST['password']??'');
    $s=db()->prepare('SELECT id,username,email,full_name,password_hash,role FROM users WHERE (username=? OR email=?) AND status=1 LIMIT 1');$s->execute([$identity,$identity]);$u=$s->fetch();
    if($u && password_verify($password,$u['password_hash'])){session_regenerate_id(true);$_SESSION['user']=['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'role'=>$u['role']];redirect('dashboard');}
    flash('error','Nama pengguna atau kata sandi tidak sesuai.');redirect('login');
}
if($page!=='login') require_login();
if(in_array($page,['menu','users','reports'],true)) require_admin();
if($page==='new-order' && $_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();$type=(string)($_POST['order_type']??'');$table=trim((string)($_POST['table_number']??''));$customer=post_string('customer_name',100);$method=(string)($_POST['payment_method']??'');$status=(string)($_POST['status']??'');$paid=$method==='Tunai'?filter_var($_POST['amount_paid']??null,FILTER_VALIDATE_INT):0;$discount=filter_var($_POST['discount']??0,FILTER_VALIDATE_INT);
    if(!in_array($type,['Dine-In','Takeaway'],true)||($type==='Dine-In'&&!preg_match('/^(?:[1-9]|[1-4][0-9]|50)$/',$table))||!in_array($method,['Tunai','QRIS','Transfer Bank','Kartu Debit'],true)||!in_array($status,['Diproses','Selesai'],true)){flash('error','Lengkapi tipe pesanan, meja, status, dan metode pembayaran.');redirect('new-order');}
    $ids=$_POST['product_id']??[];$qtys=$_POST['quantity']??[];$notes=$_POST['item_note']??[];$items=[];
    foreach(is_array($ids)?$ids:[] as $i=>$raw){$id=filter_var($raw,FILTER_VALIDATE_INT);$q=filter_var($qtys[$i]??null,FILTER_VALIDATE_INT);if(!$id)continue;if(!$q||$q<1||$q>99){flash('error','Kuantitas menu harus antara 1 dan 99.');redirect('new-order');}if(!isset($items[$id]))$items[$id]=['qty'=>0,'note'=>''];$items[$id]['qty']+=$q;if($items[$id]['qty']>99){flash('error','Jumlah tiap menu maksimal 99.');redirect('new-order');}if(!$items[$id]['note'])$items[$id]['note']=mb_substr(trim((string)($notes[$i]??'')),0,160);}
    if(!$items){flash('error','Tambahkan setidaknya satu menu.');redirect('new-order');}
    try{
        $pdo=db();$pdo->beginTransaction();$productIds=array_keys($items);$marks=implode(',',array_fill(0,count($productIds),'?'));$q=$pdo->prepare("SELECT id,name,price,cost_price,stock FROM products WHERE id IN ($marks) AND status=1 FOR UPDATE");$q->execute($productIds);$products=$q->fetchAll();if(count($products)!==count($productIds))throw new RuntimeException('Menu tidak tersedia.');
        $subtotal=0;$byId=[];foreach($products as $p){$count=$items[(int)$p['id']]['qty'];if((int)$p['stock']<$count)throw new RuntimeException('Stok '.$p['name'].' tidak mencukupi.');$p['qty']=$count;$p['line_total']=(int)$p['price']*$count;$p['note']=$items[(int)$p['id']]['note'];$subtotal+=$p['line_total'];$byId[(int)$p['id']]=$p;}
        $discount=min(max(0,(int)$discount),$subtotal);$tax=(int)round(($subtotal-$discount)*.10);$total=$subtotal-$discount+$tax;$paidAmount=$method==='Tunai'?(int)$paid:$total;if($paidAmount<$total)throw new RuntimeException('Uang tunai kurang dari total tagihan.');
        $q=$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()');$invoice=sprintf('INV-POS-%s-%04d',date('Ymd'),(int)$q->fetchColumn()+1);
        $s=$pdo->prepare('INSERT INTO orders(invoice_number,user_id,order_type,table_number,customer_name,subtotal,tax_amount,discount_amount,grand_total,payment_method,amount_paid,change_amount,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute([$invoice,current_user()['id'],$type,$type==='Dine-In'?$table:null,$customer?:null,$subtotal,$tax,$discount,$total,$method,$paidAmount,$paidAmount-$total,$status]);$orderId=(int)$pdo->lastInsertId();
        $detail=$pdo->prepare('INSERT INTO order_details(order_id,product_id,product_name,unit_price,unit_cost,quantity,line_subtotal,special_note) VALUES(?,?,?,?,?,?,?,?)');$stock=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
        foreach($byId as $id=>$p){$detail->execute([$orderId,$id,$p['name'],$p['price'],$p['cost_price'],$p['qty'],$p['line_total'],$p['note']?:null]);$stock->execute([$p['qty'],$id,$p['qty']]);if($stock->rowCount()!==1)throw new RuntimeException('Stok berubah saat pesanan diproses.');}
        $pdo->commit();flash('success','Pesanan '.$invoice.' berhasil disimpan.');header('Location: index.php?page=receipt&id='.$orderId);exit;
    }catch(Throwable $ex){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log('Order save failed ['.get_class($ex).']: '.$ex->getMessage());flash('error',$ex instanceof RuntimeException?$ex->getMessage():'Pesanan gagal disimpan. Periksa log server untuk detail.');redirect('new-order');}
}
if($page==='menu' && $_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$action=(string)($_POST['action']??'');
    if($action==='save-product'){$id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT)?:0;$name=post_string('name',100);$code=post_string('code',30);$cat=(int)($_POST['category_id']??0);$price=filter_var($_POST['price']??null,FILTER_VALIDATE_INT);$cost=filter_var($_POST['cost']??0,FILTER_VALIDATE_INT);$stock=filter_var($_POST['stock']??null,FILTER_VALIDATE_INT);$unit=post_string('unit',20);
        if(!$name||!$code||!$cat||$price===false||$price<0||$cost===false||$cost<0||$stock===false||$stock<0||!$unit)flash('error','Periksa data menu, harga, modal, dan stok.');else{try{if($id)db()->prepare('UPDATE products SET category_id=?,code=?,name=?,price=?,cost_price=?,stock=?,unit=? WHERE id=?')->execute([$cat,$code,$name,$price,$cost,$stock,$unit,$id]);else db()->prepare('INSERT INTO products(category_id,code,name,price,cost_price,stock,unit) VALUES(?,?,?,?,?,?,?)')->execute([$cat,$code,$name,$price,$cost,$stock,$unit]);flash('success','Data menu tersimpan.');}catch(Throwable){flash('error','Kode menu telah digunakan atau kategori tidak tersedia.');}}}
    elseif($action==='save-category'){$name=post_string('category_name',70);if(!$name)flash('error','Nama kategori wajib diisi.');else{try{db()->prepare('INSERT INTO categories(name,slug) VALUES(?,?)')->execute([$name,strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'))]);flash('success','Kategori ditambahkan.');}catch(Throwable){flash('error','Kategori tersebut sudah ada.');}}}
    elseif($action==='toggle-product'){db()->prepare('UPDATE products SET status=IF(status=1,0,1) WHERE id=?')->execute([(int)($_POST['id']??0)]);flash('success','Status menu diperbarui.');}redirect('menu');
}
if($page==='users'&&$_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if(($_POST['action']??'')==='toggle-user'){$id=(int)($_POST['id']??0);if($id===current_user()['id'])flash('error','Akun yang sedang digunakan tidak dapat dinonaktifkan.');else{db()->prepare('UPDATE users SET status=IF(status=1,0,1) WHERE id=?')->execute([$id]);flash('success','Status pengguna diperbarui.');}redirect('users');}
    if(($_POST['action']??'')==='update-name'){$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$name=post_string('full_name',100);if(!$id||$id<1||$name==='')flash('error','Nama lengkap wajib diisi.');else{db()->prepare('UPDATE users SET full_name=? WHERE id=?')->execute([$name,$id]);flash('success','Nama pengguna berhasil diperbarui.');}redirect('users');}    $name=post_string('full_name',100);$username=post_string('username',40);$email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL);$password=(string)($_POST['password']??'');$role=(string)($_POST['role']??'');
    if(!$name||!preg_match('/^[A-Za-z0-9_.-]{3,40}$/',$username)||!$email||strlen($password)<8||!in_array($role,['admin','kasir'],true))flash('error','Lengkapi data. Kata sandi minimal 8 karakter.');else{try{db()->prepare('INSERT INTO users(username,email,password_hash,full_name,role) VALUES(?,?,?,?,?)')->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),$name,$role]);flash('success','Akun pengguna dibuat.');}catch(Throwable){flash('error','Username atau email sudah digunakan.');}}redirect('users');
}
if($page==='orders'&&$_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$next=(string)($_POST['status']??'');
    if($id&&in_array($next,['Selesai','Dibatalkan'],true)){
        try{$pdo=db();$pdo->beginTransaction();$q=$pdo->prepare('SELECT status FROM orders WHERE id=?'.(is_admin()?'':' AND user_id=?').' FOR UPDATE');$args=[$id];if(!is_admin())$args[]=current_user()['id'];$q->execute($args);$old=$q->fetchColumn();
            if($old==='Diproses'){$pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$next,$id]);if($next==='Dibatalkan'){$s=$pdo->prepare('SELECT product_id,quantity FROM order_details WHERE order_id=? AND product_id IS NOT NULL');$s->execute([$id]);$restore=$pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');foreach($s as $item)$restore->execute([$item['quantity'],$item['product_id']]);}}
            $pdo->commit();flash($old==='Diproses'?'success':'error',$old==='Diproses'?'Status pesanan diperbarui.':'Pesanan sudah tidak dapat diubah.');
        }catch(Throwable){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();flash('error','Status pesanan gagal diperbarui.');}
    }else flash('error','Perubahan status tidak valid.');redirect('orders');
}
function categories():array{return db()->query('SELECT id,name,slug FROM categories WHERE status=1 ORDER BY name')->fetchAll();}
function products():array{return db()->query('SELECT p.*,c.name category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.name')->fetchAll();}
function orders(string $where='',array $args=[],int $limit=50):array{$q=db()->prepare('SELECT o.*,u.full_name cashier FROM orders o JOIN users u ON u.id=o.user_id WHERE 1=1 '.$where.' ORDER BY o.created_at DESC LIMIT '.max(1,min(500,$limit)));$q->execute($args);return $q->fetchAll();}
$flash=$_SESSION['flash']??null;unset($_SESSION['flash']);
if($page!=='login'){$title=['dashboard'=>'Ringkasan hari ini','orders'=>'Pesanan','new-order'=>'Pesanan baru','receipt'=>'Nota pesanan','reports'=>'Laporan penjualan','menu'=>'Menu & stok','users'=>'Pengguna'][$page]??APP_NAME;require __DIR__.'/views/header.php';}
switch($page){case 'login':require __DIR__.'/views/login.php';break;case 'dashboard':require __DIR__.'/views/dashboard.php';break;case 'orders':require __DIR__.'/views/orders.php';break;case 'new-order':require __DIR__.'/views/new-order.php';break;case 'receipt':require __DIR__.'/views/receipt.php';break;case 'reports':require __DIR__.'/views/reports.php';break;case 'menu':require __DIR__.'/views/menu.php';break;case 'users':require __DIR__.'/views/users.php';break;}
if($page!=='login')require __DIR__.'/views/footer.php';
