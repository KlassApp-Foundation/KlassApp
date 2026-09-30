const { chromium } = require('playwright');
const BASE='https://klassapp-staging-7mpoqg.laravel.cloud';
(async()=>{const b=await chromium.launch();const p=await (await b.newContext({viewport:{width:1280,height:900}})).newPage();
await p.goto(BASE+'/login',{waitUntil:'load',timeout:90000});
await p.fill('input[name=email]','phase4.admin@klassapp.xyz');await p.fill('input[name=password]',process.env.PW);
await Promise.all([p.waitForNavigation({waitUntil:'load',timeout:90000}).catch(()=>null),p.click('[data-testid="ap-primary-submit"], button[type=submit]')]);
await p.waitForTimeout(2500);console.log('after login',p.url());
const r=await p.goto(BASE+'/admin/attendance/add',{waitUntil:'networkidle',timeout:90000});await p.waitForTimeout(2000);
console.log('status',r.status(),p.url());
console.log(await p.evaluate(()=>({text:document.body.innerText.replace(/\s+/g,' ').slice(0,900),
 selects:[...document.querySelectorAll('select')].map(s=>({n:s.name,id:s.id,o:[...s.options].slice(0,10).map(o=>o.value+':'+o.text.trim())})),
 inputs:[...document.querySelectorAll('input')].filter(i=>i.type!='hidden').map(i=>i.name+'|'+i.type+'|'+i.id).slice(0,20),
 buttons:[...document.querySelectorAll('button')].map(x=>x.innerText.trim()).filter(Boolean).slice(0,30)})));
await p.screenshot({path:'/tmp/kstg/att-add.png',fullPage:true});await b.close();})();
