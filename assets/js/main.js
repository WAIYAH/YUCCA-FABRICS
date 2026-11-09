document.addEventListener('DOMContentLoaded',()=>{
  const toggle=document.getElementById('menu-toggle');
  const mobile=document.getElementById('mobile-nav');
  if(toggle&&mobile)toggle.addEventListener('click',()=>{const open=mobile.classList.toggle('hidden')===false;toggle.setAttribute('aria-expanded',String(open));});
  const search=document.getElementById('search');
  const suggestions=document.getElementById('search-suggestions');
  if(search&&suggestions){
    let timer;
    search.addEventListener('input',()=>{
      clearTimeout(timer);
      timer=setTimeout(async()=>{
        const query=search.value.trim();
        if(query.length<2){suggestions.replaceChildren();suggestions.classList.add('hidden');return;}
        try{
          const response=await fetch(`${window.YUCCA.base}/api/search.php?q=${encodeURIComponent(query)}`);
          const result=await response.json();
          suggestions.replaceChildren();
          for(const item of result.results||[]){const link=document.createElement('a');link.className='block border-b border-black/5 px-4 py-3 text-sm hover:bg-sand';link.href=`${window.YUCCA.base}/product-details.php?slug=${encodeURIComponent(item.slug)}`;link.textContent=item.colour?`${item.name} · ${item.colour}`:item.name;suggestions.appendChild(link);}
          suggestions.classList.toggle('hidden',suggestions.childElementCount===0);
        }catch(error){suggestions.replaceChildren();suggestions.classList.add('hidden');notify('Live search is temporarily unavailable.',true);}
      },180);
    });
    document.addEventListener('click',event=>{if(!search.contains(event.target)&&!suggestions.contains(event.target))suggestions.classList.add('hidden');});
  }
  const heroSlides=[...document.querySelectorAll('[data-hero-slide]')];
  if(heroSlides.length>1){
    let active=0,paused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const count=document.querySelector('[data-hero-count]');
    const render=()=>{heroSlides.forEach((slide,index)=>slide.classList.toggle('opacity-60',index===active));heroSlides.forEach((slide,index)=>slide.classList.toggle('opacity-0',index!==active));if(count)count.textContent=`${String(active+1).padStart(2,'0')} / ${String(heroSlides.length).padStart(2,'0')}`;};
    const next=()=>{active=(active+1)%heroSlides.length;render();};
    document.querySelector('[data-hero-prev]')?.addEventListener('click',()=>{active=(active+heroSlides.length-1)%heroSlides.length;render();});
    document.querySelector('[data-hero-next]')?.addEventListener('click',next);
    const pause=document.querySelector('[data-hero-toggle]');
    if(pause){pause.setAttribute('aria-pressed',String(paused));pause.textContent=paused?'Play':'Pause';}
    pause?.addEventListener('click',()=>{paused=!paused;pause.setAttribute('aria-pressed',String(paused));pause.textContent=paused?'Play':'Pause';});
    setInterval(()=>{if(!paused)next();},6000);
  }
  document.querySelectorAll('form[data-ajax-form]').forEach(form=>form.addEventListener('submit',async event=>{
    event.preventDefault();
    const button=form.querySelector('button[type="submit"],button:not([type])');
    if(button)button.disabled=true;
    try{const response=await fetch(form.action,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf,'Accept':'application/json'},body:new FormData(form)});const result=await response.json();if(!response.ok)throw new Error(result.error||'Please check the form and try again.');notify(result.message||'Thank you.');form.reset();if(form.dataset.reload==='true')window.location.reload();}
    catch(error){notify(error.message,true);}finally{if(button)button.disabled=false;}
  }));
  document.querySelectorAll('[data-add-cart]').forEach(button=>button.addEventListener('click',async()=>{
    const form=new FormData();form.append('action','add');form.append('product_id',button.dataset.addCart);form.append('quantity',document.querySelector('[name="quantity"]')?.value||1);
    button.disabled=true;
    try{const response=await fetch(`${window.YUCCA.base}/api/cart.php`,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf},body:form});const result=await response.json();if(!response.ok)throw new Error(result.error||'Unable to update your bag.');const count=document.getElementById('cart-count');if(count)count.textContent=result.count;notify(result.message||'Added to your bag.');}
    catch(error){notify(error.message,true);}finally{button.disabled=false;}
  }));
  document.querySelectorAll('[data-add-wishlist],[data-remove-wishlist]').forEach(button=>button.addEventListener('click',async()=>{
    const form=new FormData();form.append('action',button.hasAttribute('data-remove-wishlist')?'remove':'add');form.append('product_id',button.dataset.addWishlist||button.dataset.removeWishlist);
    try{const response=await fetch(`${window.YUCCA.base}/api/wishlist.php`,{method:'POST',headers:{'X-CSRF-Token':window.YUCCA.csrf},body:form});const result=await response.json();if(!response.ok)throw new Error(result.error||'Unable to update favourites.');notify(result.message);if(button.hasAttribute('data-remove-wishlist'))button.closest('.relative')?.remove();}
    catch(error){notify(error.message,true);}
  }));
  document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.confirm)){event.preventDefault();}}));
});
function notify(message,isError=false){const toast=document.createElement('div');toast.className='toast';toast.style.background=isError?'#8f4038':'#34483a';toast.textContent=message;document.body.appendChild(toast);setTimeout(()=>toast.remove(),3500);}
