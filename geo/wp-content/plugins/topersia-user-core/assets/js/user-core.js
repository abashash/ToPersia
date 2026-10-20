(function(){
  'use strict';
  function ready(fn){document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();}
  ready(function(){
    var cfg=window.ToPersiaUserCore||{};
    var T=cfg.i18n||{};
    /* ۱٫۱۲٫۱ · P5-D374 — نانس مهمان در HTML نیست؛ هنگام نیاز از REST بی‌کش گرفته و در حافظه نگه داشته می‌شود */
    var nonceP=null;
    function getNonce(){
      if(cfg.nonce)return Promise.resolve(cfg.nonce);
      if(!nonceP){
        nonceP=fetch(cfg.nonceUrl||'',{credentials:'same-origin',cache:'no-store'})
          .then(function(r){return r.json();})
          .then(function(j){nonceP=null;cfg.nonce=(j&&j.nonce)||'';return cfg.nonce;})
          .catch(function(e){nonceP=null;throw e;});
      }
      return nonceP;
    }
    function ajax(action,data){
      return getNonce().then(function(nonce){
        var payload=new URLSearchParams();payload.set('action',action);payload.set('nonce',nonce||'');
        Object.keys(data||{}).forEach(function(k){
          var v=data[k];
          if(Array.isArray(v)){v.forEach(function(one){payload.append(k+'[]',one==null?'':one);});return;}
          payload.set(k,v==null?'':v);
        });
        return fetch(cfg.ajaxUrl||'',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString(),credentials:'same-origin'})
          .then(function(r){return r.json().catch(function(){throw new Error('HTTP '+r.status);});});
      });
    }
    function upload(action,file){
      return getNonce().then(function(nonce){
        var fd=new FormData();fd.append('action',action);fd.append('nonce',nonce||'');if(file)fd.append('avatar',file);
        return fetch(cfg.ajaxUrl||'',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json().catch(function(){throw new Error('HTTP '+r.status);});});
      });
    }
    /* «هستۀ نمایش» و هر افزونۀ دیگر از همین در استفاده می‌کنند؛ نانس و
       نشانی یک‌جا می‌مانند.  ۱٫۱۰٫۰ */
    cfg.call=ajax;
    function setMessage(id,text,ok){var el=document.getElementById(id);if(!el)return;el.textContent=text||'';el.className='tp-message'+(ok?' success':' error');}
    function activateTab(root,tab,push){
      var target=null;
      root.querySelectorAll('[data-tp-panel]').forEach(function(panel){
        if(panel.getAttribute('data-tp-panel')===tab)target=panel;
      });
      if(!target){
        tab=root.getAttribute('data-tp-default-tab')||'overview';
        root.querySelectorAll('[data-tp-panel]').forEach(function(panel){
          if(panel.getAttribute('data-tp-panel')===tab)target=panel;
        });
      }
      if(!target){
        var first=root.querySelector('[data-tp-panel]');
        if(!first)return;
        tab=first.getAttribute('data-tp-panel');
      }
      root.querySelectorAll('[data-tp-panel]').forEach(function(panel){
        var on=panel.getAttribute('data-tp-panel')===tab;
        panel.classList.toggle('active',on);
        panel.hidden=!on;
        panel.setAttribute('aria-hidden',on?'false':'true');
      });
      root.querySelectorAll('[data-tp-tab][role="tab"]').forEach(function(button){
        var on=button.getAttribute('data-tp-tab')===tab;
        button.classList.toggle('active',on);
        button.setAttribute('aria-selected',on?'true':'false');
        button.setAttribute('tabindex',on?'0':'-1');
      });
      if(push){
        var u=window.location.href.split('#')[0];
        window.history.replaceState(null,'',u+'#'+encodeURIComponent(tab));
      }
      root.dispatchEvent(new CustomEvent('topersia:tab-change',{detail:{tab:tab},bubbles:false}));
    }
    document.querySelectorAll('[data-tp-dashboard]').forEach(function(root){
      root.addEventListener('click',function(e){var t=e.target.closest('[data-tp-tab]');if(!t||!root.contains(t))return;e.preventDefault();activateTab(root,t.getAttribute('data-tp-tab')||'overview',true);});
      root.addEventListener('keydown',function(e){var b=e.target.closest('[data-tp-tab][role="tab"]');if(!b||!root.contains(b))return;var a=[].slice.call(root.querySelectorAll('[data-tp-tab][role="tab"]')),i=a.indexOf(b),n=i;if(e.key==='ArrowRight'||e.key==='ArrowDown')n=(i+1)%a.length;if(e.key==='ArrowLeft'||e.key==='ArrowUp')n=(i-1+a.length)%a.length;if(e.key==='Home')n=0;if(e.key==='End')n=a.length-1;if(n!==i){e.preventDefault();a[n].focus();activateTab(root,a[n].getAttribute('data-tp-tab'),true);}});
      var h=window.location.hash.replace(/^#/,'');var initial='overview';try{if(h)initial=decodeURIComponent(h);}catch(_e){}activateTab(root,initial,false);
    });
    var saveHandler=function(e){var button=e.target.closest('.tp-save-entity');if(!button)return;var saved=button.classList.contains('is-saved');button.disabled=true;ajax(saved?'topersia_remove_saved_place':'topersia_save_place',{post_id:button.getAttribute('data-post-id')||'',post_type:button.getAttribute('data-post-type')||''}).then(function(r){if(!r.success)return;button.classList.toggle('is-saved',!saved);var L=window.ToPersiaUserCore||{};button.textContent=saved?(L.saveLabel||'Save'):'✓ '+(L.savedLabel||'Saved');button.setAttribute('aria-pressed',saved?'false':'true');button.title=saved?(L.saveLabel||'Save'):(L.removeSavedLabel||'Remove from saved');document.querySelectorAll('[data-tp-saved-count]').forEach(function(c){if(r.data&&typeof r.data.count!=='undefined')c.textContent=String(r.data.count);});}).catch(function(){}).finally(function(){button.disabled=false;});};
    document.addEventListener('click',saveHandler);

    /* لغو دنبال‌کردن از پنل حساب — کارت همان‌جا برداشته می‌شود.  ۱٫۱۰٫۰ */
    document.addEventListener('click',function(e){
      var b=e.target.closest?e.target.closest('[data-tp-unfollow]'):null;if(!b)return;
      b.disabled=true;
      ajax('topersia_follow_entity',{post_id:b.getAttribute('data-post-id')||'',post_type:b.getAttribute('data-post-type')||'',mode:'unfollow'}).then(function(r){
        if(!r||!r.success){b.disabled=false;return;}
        var card=b.closest('[data-tp-follow-card]');if(card)card.remove();
        document.querySelectorAll('[data-tp-follow-count]').forEach(function(c){c.textContent=String(r.data.count);});
        var list=document.querySelector('[data-tp-follow-list]');
        if(list&&!list.querySelector('[data-tp-follow-card]')){var empty=document.querySelector('[data-tp-follow-empty]');if(empty)empty.hidden=false;list.hidden=true;}
      }).catch(function(){b.disabled=false;});
    });

    /* ══ ورود با پیامک ═══════════════════════════════════════════════
       تا ۱٫۹٫۱۲ دکمه‌های فرم ورود هیچ دستگیره‌ای نداشتند — فرم روی سایت
       زنده کاری نمی‌کرد. هر کارت جدا بسته می‌شود؛ شناسه‌ها درون همان
       کارت جست‌وجو می‌شوند، نه سراسری.  ۱٫۱۰٫۰
       ────────────────────────────────────────────────────────────── */
    function bindAuth(card){
      if(card.getAttribute('data-tp-bound'))return;card.setAttribute('data-tp-bound','1');
      var mobile=card.querySelector('#tp-mobile'),send=card.querySelector('#tp-send-otp'),box=card.querySelector('#tp-otp-box'),code=card.querySelector('#tp-otp'),verify=card.querySelector('#tp-verify-otp'),msg=card.querySelector('#tp-auth-message');
      var email=card.querySelector('#tp-email'),sendMail=card.querySelector('#tp-send-email');
      var tabs=[].slice.call(card.querySelectorAll('[data-tp-method][role="tab"]'));
      var token='',method=card.getAttribute('data-tp-default-method')||'mobile';
      function say(text,ok){if(!msg)return;msg.textContent=text||'';msg.className='tp-message'+(ok?' success':' error');}
      function fail(r){say((r&&r.data&&r.data.message)||T.networkError||'Error',false);}
      /* ۱٫۱۴٫۰ · LOGIN.md §۶ — دو کانال در زبانه؛ پیش‌فرض از زبان صفحه، ترجیح کاربر در localStorage */
      function setMethod(m,focus,save){
        if(m!=='mobile'&&m!=='email')return;
        method=m;token='';
        tabs.forEach(function(t){var on=t.getAttribute('data-tp-method')===m;t.setAttribute('aria-selected',on?'true':'false');t.setAttribute('tabindex',on?'0':'-1');});
        [].forEach.call(card.querySelectorAll('[data-tp-method-panel]'),function(p){p.hidden=p.getAttribute('data-tp-method-panel')!==m;});
        if(box)box.classList.add('tp-hidden');if(code)code.value='';say('',true);
        if(save){try{window.localStorage.setItem('tp-login-method',m);}catch(_e){}}
        if(focus){var f=m==='email'?email:mobile;if(f)f.focus();}
      }
      try{var pref=window.localStorage.getItem('tp-login-method');if(pref==='mobile'||pref==='email')setMethod(pref,false,false);}catch(_e){}
      tabs.forEach(function(t){
        t.addEventListener('click',function(){setMethod(t.getAttribute('data-tp-method'),true,true);});
        t.addEventListener('keydown',function(e){
          /* جهت از صفحه (P5-D243): در راست‌به‌چپ «بعدی» = فلش چپ */
          var rtl=window.getComputedStyle(card).direction==='rtl',i=tabs.indexOf(t),n=i;
          if(e.key==='ArrowRight')n=rtl?i-1:i+1;if(e.key==='ArrowLeft')n=rtl?i+1:i-1;if(e.key==='Home')n=0;if(e.key==='End')n=tabs.length-1;
          if(n===i)return;e.preventDefault();n=(n+tabs.length)%tabs.length;tabs[n].focus();setMethod(tabs[n].getAttribute('data-tp-method'),false,true);
        });
      });
      function sent(r){
        if(!r||!r.success){fail(r);return;}
        token=r.data.token||'';if(box)box.classList.remove('tp-hidden');say(r.data.message||'',true);if(code)code.focus();
      }
      if(send)send.addEventListener('click',function(){
        send.disabled=true;say(T.otpSending||'',true);
        ajax('topersia_send_otp',{mobile:mobile?mobile.value:''}).then(sent).catch(function(){fail();}).finally(function(){send.disabled=false;});
      });
      if(sendMail)sendMail.addEventListener('click',function(){
        sendMail.disabled=true;say(T.otpSending||'',true);
        ajax('topersia_email_code_send',{email:email?email.value:'',lang:cfg.lang||''}).then(sent).catch(function(){fail();}).finally(function(){sendMail.disabled=false;});
      });
      if(verify)verify.addEventListener('click',function(){
        verify.disabled=true;say(T.otpChecking||'',true);
        var back=card.getAttribute('data-tp-redirect')||'';
        var call=method==='email'
          ?ajax('topersia_email_code_verify',{token:token,otp:code?code.value:'',email:email?email.value:'',redirect:back,lang:cfg.lang||''})
          :ajax('topersia_verify_otp',{token:token,otp:code?code.value:'',mobile:mobile?mobile.value:'',redirect:back});
        call.then(function(r){
          if(!r||!r.success){fail(r);verify.disabled=false;return;}
          window.location.href=(r.data&&r.data.redirect)||cfg.loginUrl||'/';
        }).catch(function(){fail();verify.disabled=false;});
      });
      card.tpFocus=function(){var f=method==='email'?email:mobile;if(f)f.focus();};
    }
    document.querySelectorAll('[data-tp-auth]').forEach(bindAuth);

    /* ══ پیوند شناسۀ دوم از پنل (ایمیل یا موبایل) — ۱٫۱۴٫۰ · LOGIN.md §۲-پ ══ */
    [].forEach.call(document.querySelectorAll('[data-tp-ident]'),function(row){
      var kind=row.getAttribute('data-tp-ident'),input=row.querySelector('[data-tp-ident-input]'),sendB=row.querySelector('[data-tp-ident-send]'),box=row.querySelector('[data-tp-ident-box]'),codeI=row.querySelector('[data-tp-ident-code]'),ok=row.querySelector('[data-tp-ident-verify]');
      var token='';
      function say(t,good){setMessage('tp-ident-message',t,good);}
      function fail(r){say((r&&r.data&&r.data.message)||T.networkError||'Error',false);}
      sendB.addEventListener('click',function(){
        sendB.disabled=true;say(T.otpSending||'',true);
        var call=kind==='email'?ajax('topersia_email_code_send',{email:input.value,purpose:'link',lang:cfg.lang||''}):ajax('topersia_send_otp',{mobile:input.value,purpose:'link'});
        call.then(function(r){if(!r||!r.success){fail(r);return;}token=r.data.token||'';box.classList.remove('tp-hidden');say(r.data.message||'',true);codeI.focus();}).catch(function(){fail();}).finally(function(){sendB.disabled=false;});
      });
      ok.addEventListener('click',function(){
        ok.disabled=true;say(T.otpChecking||'',true);
        var call=kind==='email'?ajax('topersia_email_code_verify',{token:token,otp:codeI.value,email:input.value,lang:cfg.lang||''}):ajax('topersia_verify_otp',{token:token,otp:codeI.value,mobile:input.value});
        call.then(function(r){if(!r||!r.success){fail(r);ok.disabled=false;return;}say((r.data&&r.data.message)||'',true);window.setTimeout(function(){window.location.reload();},900);}).catch(function(){fail();ok.disabled=false;});
      });
    });

    /* ══ پنجرۀ ورود/ثبت‌نام ═════════════════════════════════════════
       فقط برای مهمان چاپ می‌شود. «هستۀ نمایش» با رویداد
       `topersia:login-required` بازش می‌کند؛ نشانی بازگشت همان صفحه است.
       ────────────────────────────────────────────────────────────── */
    var modal=document.getElementById('tp-uc-login');
    if(modal){
      if(modal.parentNode!==document.body)document.body.appendChild(modal);
      var last=null;
      var reasons={save:T.reasonSave,follow:T.reasonFollow,review:T.reasonReview,ai:T.reasonAi};
      var open=function(detail){
        detail=detail||{};
        var card=modal.querySelector('[data-tp-auth]');
        if(card)card.setAttribute('data-tp-redirect',detail.redirect||window.location.href);
        var why=modal.querySelector('[data-tp-uc-reason]');
        if(why)why.textContent=reasons[detail.reason]||'';
        last=document.activeElement;
        modal.hidden=false;
        document.documentElement.classList.add('tp-uc-login-open');
        if(card&&card.tpFocus)card.tpFocus();
      };
      var close=function(){
        modal.hidden=true;
        document.documentElement.classList.remove('tp-uc-login-open');
        if(last&&last.focus)last.focus();
      };
      cfg.openLogin=open;
      document.addEventListener('topersia:login-required',function(e){open(e.detail);});
      modal.addEventListener('click',function(e){if(e.target===modal||(e.target.closest&&e.target.closest('[data-tp-uc-close]')))close();});
      document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)close();});
      document.addEventListener('click',function(e){
        var a=e.target.closest?e.target.closest('[data-tp-login]'):null;if(!a)return;
        e.preventDefault();open({reason:a.getAttribute('data-tp-login')||'',redirect:window.location.href});
      });
    }

    var logout=document.getElementById('tp-logout');if(logout){logout.addEventListener('click',function(){logout.disabled=true;ajax('topersia_logout',{}).then(function(r){if(r.success)window.location.href=r.data.redirect;}).catch(function(){logout.disabled=false;});});}
    /* عکس نمایه — P5-D377 */
    (function(){var card=document.querySelector('[data-tp-avatar-card]');if(!card)return;var f=document.getElementById('tp-avatar-file'),pick=document.getElementById('tp-avatar-choose'),rm=document.getElementById('tp-avatar-remove');
      function show(u){[].forEach.call(document.querySelectorAll('[data-tp-avatar-img]'),function(i){i.src=u;});}
      function done(r){setMessage('tp-avatar-message',(r.data&&r.data.message)||'',!!r.success);if(r.success&&r.data&&r.data.url)show(r.data.url);}
      pick.addEventListener('click',function(){f.click();});
      f.addEventListener('change',function(){var file=f.files&&f.files[0];if(!file)return;pick.disabled=true;upload('topersia_avatar_upload',file).then(done).catch(function(){setMessage('tp-avatar-message','Error',false);}).finally(function(){pick.disabled=false;f.value='';});});
      rm.addEventListener('click',function(){rm.disabled=true;ajax('topersia_avatar_delete',{}).then(done).catch(function(){setMessage('tp-avatar-message','Error',false);}).finally(function(){rm.disabled=false;});});
    })();
    var profile=document.getElementById('tp-profile-form');if(profile){profile.addEventListener('submit',function(e){e.preventDefault();var btn=profile.querySelector('button[type="submit"]');if(btn)btn.disabled=true;var d={};new FormData(profile).forEach(function(v,k){d[k]=v;});ajax('topersia_save_profile',d).then(function(r){setMessage('tp-profile-message',(r.data&&r.data.message)||'',!!r.success);if(r.success&&d.language){document.documentElement.setAttribute('data-tp-user-lang',d.language);}}).catch(function(){setMessage('tp-profile-message','Error',false);}).finally(function(){if(btn)btn.disabled=false;});});}
    /* تمام‌صفحه — سه پله، چون position:fixed اگر یکی از نیاکان
       transform یا filter یا contain داشته باشد، به آن نیا قفل می‌شود
       و عنصر به جای دیدگاه، ته سند می‌نشیند.

       ۱٫۹٫۱۲: کد گزارش‌دهی تشخیص (report و فراخوانی topersia_log_js)
       کاملاً حذف شد. آن کد به کنشی پیام می‌فرستاد که در هیچ فایل
       PHP ثبت نشده بود، پس همۀ گزارش‌ها به پاسخ -1 می‌رسیدند و دور
       ریخته می‌شدند (نونس هم نمی‌فرستاد). خودِ سه پله مانده، چون
       مکانیزم اجراست و قالب WoodMart روی برخی قالب‌بندی‌ها بلوک
       احاطه‌کننده می‌سازد. */
    var dash=document.querySelector('[data-tp-fullscreen="1"]');
    if(dash){
      var pinned=function(){
        var cs=window.getComputedStyle(dash);
        if(cs.position!=='fixed')return false;
        /* افقی سنجیده نمی‌شود: 100vw عرض نوار پیمایش را هم می‌شمارد و در
           RTL عنصر چند پیکسل به چپ می‌رود. ملاک، چسبیدن به بالای دیدگاه است. */
        return Math.abs(dash.getBoundingClientRect().top)<=2;
      };

      /* پلۀ یک — انتقال به body و کلاس‌ها */
      if(dash.parentNode!==document.body)document.body.appendChild(dash);
      document.documentElement.classList.add('tp-usercore-fullscreen');
      document.body.classList.add('tp-usercore-open');

      /* پلۀ دو — سبک درون‌خطی، اگر شیوه‌نامه‌ای بازنویسی کرده باشد */
      if(!pinned()){
        dash.style.setProperty('position','fixed','important');
        dash.style.setProperty('inset','0','important');
        dash.style.setProperty('height','100dvh','important');
        dash.style.setProperty('z-index','999999','important');
        dash.style.setProperty('overflow','auto','important');
      }

      /* پلۀ سه — بیرون‌بردن از body، برای وقتی که خود body بلوک احاطه‌کننده
         ساخته است. آن‌وقت هیچ نیایی جز html نمی‌ماند. */
      if(!pinned()){
        document.documentElement.appendChild(dash);
      }
    }

    /* ── حذف حساب و صدور داده — تأیید دومرحله‌ای (۱٫۱۲٫۰ · P5-D375) ── */
    (function(){
      var card=document.querySelector('[data-tp-dr-card]');if(!card)return;
      var box=document.getElementById('tp-dr-confirm'),txt=document.getElementById('tp-dr-confirm-text'),ok=document.getElementById('tp-dr-confirm-btn'),no=document.getElementById('tp-dr-cancel'),msg=document.getElementById('tp-dr-message');
      var cur='',token='';
      function say(t,good){if(!msg)return;msg.textContent=t||'';msg.className='tp-message'+(good?' success':' error');}
      function reset(){cur='';token='';if(box)box.hidden=true;}
      [].forEach.call(card.querySelectorAll('[data-tp-dr]'),function(b){
        b.addEventListener('click',function(){
          cur=b.getAttribute('data-tp-dr');say('',true);b.disabled=true;
          ajax('topersia_data_request',{type:cur,step:'start'}).then(function(r){
            if(!r||!r.success){say((r&&r.data&&r.data.message)||T.networkError||'Error',false);b.disabled=false;reset();return;}
            token=r.data.token||'';if(txt)txt.textContent=r.data.message||'';if(box)box.hidden=false;b.disabled=false;
          }).catch(function(){say(T.networkError||'Error',false);b.disabled=false;reset();});
        });
      });
      if(no)no.addEventListener('click',function(){reset();say('',true);});
      if(ok)ok.addEventListener('click',function(){
        if(!cur||!token)return;ok.disabled=true;
        ajax('topersia_data_request',{type:cur,step:'confirm',token:token}).then(function(r){
          var good=!!(r&&r.success);say((r&&r.data&&r.data.message)||T.networkError||'Error',good);
          if(good){var b=card.querySelector('[data-tp-dr="'+cur+'"]');if(b)b.disabled=true;}
          reset();
        }).catch(function(){say(T.networkError||'Error',false);reset();}).finally(function(){ok.disabled=false;});
      });
    })();
  });
})();
