document.addEventListener('DOMContentLoaded',()=>{
  document.body.classList.add('page-entered');
  const menuButton=document.querySelector('.mobile-menu');
  const scrim=document.querySelector('.scrim');
  menuButton?.addEventListener('click',()=>document.body.classList.toggle('nav-open'));
  scrim?.addEventListener('click',()=>document.body.classList.remove('nav-open'));
  document.querySelector('.reveal')?.addEventListener('click',event=>{const input=document.querySelector('#password');if(!input)return;input.type=input.type==='password'?'text':'password';event.currentTarget.textContent=input.type==='password'?'Lihat':'Sembunyi'});

  const grid=document.querySelector('#product-grid');
  const search=document.querySelector('#menu-search');
  let activeCategory='all';
  const filterProducts=()=>{
    if(!grid)return;
    const query=(search?.value||'').trim().toLocaleLowerCase('id-ID');
    let visible=0;
    grid.querySelectorAll('.product-tile').forEach(card=>{
      const matchesCategory=activeCategory==='all'||card.dataset.category===activeCategory;
      const matchesName=(card.dataset.name||'').includes(query);
      card.hidden=!(matchesCategory&&matchesName);
      if(!card.hidden)visible++;
    });
    const empty=document.querySelector('.menu-empty');
    if(empty)empty.hidden=visible>0;
  };
  search?.addEventListener('input',filterProducts);
  document.querySelectorAll('.category-chip').forEach(button=>button.addEventListener('click',()=>{
    activeCategory=button.dataset.filter||'all';
    document.querySelectorAll('.category-chip').forEach(chip=>chip.classList.toggle('active',chip===button));
    filterProducts();
  }));

  const form=document.querySelector('#order-form');
  const cart=document.querySelector('#order-lines');
  const template=document.querySelector('#order-line-template');
  const method=document.querySelector('[name="payment_method"]');
  const cashField=document.querySelector('.cash-field');
  const amount=document.querySelector('#amount-paid');
  const discount=document.querySelector('#discount');
  const money=value=>'Rp '+Math.round(value).toLocaleString('id-ID');
  const syncItemNotes=()=>cart?.querySelectorAll('.cart-row').forEach(row=>{
    const payload=row.querySelector('.item-note-payload');
    if(!payload)return;
    if(row.dataset.drink==='1'){
      const sugar=row.querySelector('.drink-sugar')?.value||'Normal';
      const ice=row.querySelector('.drink-ice')?.value||'Normal';
      const extra=row.querySelector('.item-note-extra')?.value.trim();
      payload.value='Gula: '+sugar+'; Es: '+ice+(extra?'; '+extra:'');
    }else payload.value=row.querySelector('.food-note')?.value.trim()||'';
  });
  const recalc=()=>{
    if(!cart)return;
    let subtotal=0,units=0;
    cart.querySelectorAll('.cart-row').forEach(row=>{
      const qty=row.querySelector('[name="quantity[]"]');
      subtotal+=Number(row.dataset.price||0)*Number(qty?.value||0);
      units+=Number(qty?.value||0);
    });
    const off=Math.min(Math.max(0,Number(discount?.value||0)),subtotal);
    const tax=Math.round((subtotal-off)*.1);
    const total=subtotal-off+tax;
    const setText=(selector,value)=>{const node=document.querySelector(selector);if(node)node.textContent=value};
    setText('#subtotal-preview',money(subtotal));
    setText('#discount-preview','− '+money(off));
    setText('#tax-preview',money(tax));
    const totalNode=document.querySelector('#grand-total');
    if(totalNode){totalNode.textContent=money(total);totalNode.dataset.total=String(total)}
    setText('#cart-count',units+' item'+(units===1?'':'s'));
    const paid=Number(amount?.value||0);
    setText('#change-display',method?.value==='Tunai'?(paid>=total&&paid>0?money(paid-total):'—'):'Non-tunai');
  };
  const addProduct=button=>{
    if(!cart||!template)return;
    const id=String(button.dataset.productId||'');
    const existing=[...cart.querySelectorAll('[name="product_id[]"]')].find(input=>input.value===id);
    if(existing){const qty=existing.closest('.cart-row').querySelector('[name="quantity[]"]');qty.value=String(Math.min(99,Number(qty.value)+1));}
    else{
      cart.querySelector('.cart-empty')?.remove();
      const row=template.content.firstElementChild.cloneNode(true);
      row.dataset.price=button.dataset.price||'0';
      row.dataset.drink=['coffee','non-coffee'].includes(button.closest('.product-tile')?.dataset.category||'')?'1':'0';
      const drinkOptions=row.querySelector('.drink-customization');
      const foodNote=row.querySelector('.food-note');
      if(drinkOptions)drinkOptions.hidden=row.dataset.drink!=='1';
      if(foodNote)foodNote.hidden=row.dataset.drink==='1';
      row.querySelector('[name="product_id[]"]').value=id;
      row.querySelector('.cart-item-name').textContent=button.dataset.productName||'Menu';
      row.querySelector('.cart-item-price').textContent=money(Number(button.dataset.price||0));
      cart.append(row);
      syncItemNotes();
    }
    button.classList.add('just-added');
    window.setTimeout(()=>button.classList.remove('just-added'),420);
    recalc();
  };
  document.addEventListener('click',event=>{
    const add=event.target.closest('.product-add');
    if(add&&!add.disabled){addProduct(add);return;}
    const step=event.target.closest('.quantity-step');
    if(step){const input=step.parentElement.querySelector('[name="quantity[]"]');const delta=step.classList.contains('increase')?1:-1;input.value=String(Math.max(1,Math.min(99,Number(input.value||1)+delta)));recalc();return;}
    const remove=event.target.closest('.remove-line');
    if(remove){remove.closest('.cart-row')?.remove();if(cart&&!cart.querySelector('.cart-row'))cart.innerHTML='<div class="cart-empty"><span>＋</span><strong>Belum ada menu</strong><small>Pilih menu di katalog untuk memulai.</small></div>';recalc();}
  });
  cart?.addEventListener('input',()=>{syncItemNotes();recalc()});
  cart?.addEventListener('change',()=>{syncItemNotes();recalc()});
  discount?.addEventListener('input',recalc);
  amount?.addEventListener('input',recalc);
  const syncPaymentFields=()=>{const isCash=method?.value==='Tunai';if(cashField)cashField.hidden=!isCash;if(amount){amount.required=isCash;amount.disabled=!isCash;if(!isCash)amount.value=''};recalc()};
  method?.addEventListener('change',syncPaymentFields);
  syncPaymentFields();
  const orderType=document.querySelector('#order-type');
  const tableField=document.querySelector('#table-field');
  const tableInput=tableField?.querySelector('input');
  tableInput?.addEventListener('invalid',()=>tableInput.setCustomValidity('Masukkan nomor meja berupa angka 1 sampai 50 untuk pesanan Dine-In.'));
  tableInput?.addEventListener('input',()=>tableInput.setCustomValidity(''));
  orderType?.addEventListener('change',()=>{const dine=orderType.value==='Dine-In';if(tableField)tableField.hidden=!dine;if(tableInput){tableInput.required=dine;tableInput.setCustomValidity('')}});
  form?.addEventListener('submit',event=>{syncItemNotes();if(!cart?.querySelector('.cart-row')){event.preventDefault();const empty=cart?.querySelector('.cart-empty');if(empty){empty.classList.add('needs-item');empty.querySelector('strong').textContent='Tambahkan menu sebelum menyimpan';}}});
  recalc();
});