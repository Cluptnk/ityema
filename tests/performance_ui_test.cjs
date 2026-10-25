const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const elements = new Map();
function element(id) {
  if (!elements.has(id)) elements.set(id, {id, value:'', textContent:'', innerHTML:'', disabled:false, listeners:{}, addEventListener(name, fn){this.listeners[name]=fn;}});
  return elements.get(id);
}
element('performance-date').value='2026-10-22';element('performance-window').value='s1';element('performance-looms').value='14';
const pending=[];
const context={document:{getElementById:element,createElement(){return {textContent:'',get innerHTML(){return this.textContent;}};}},URLSearchParams,window:{},setTimeout,clearTimeout,ReportQuality:{
  loading(){for(const id of ['performance-length','performance-speed','performance-efficiency','performance-stop-duration'])element(id).textContent='—';element('performance-results').innerHTML='';},
  show(){},error(){},request(){return new Promise((resolve,reject)=>pending.push({resolve,reject}));}
}};
vm.runInNewContext(fs.readFileSync(require.resolve('../site-dashboard/js/performance.js'),'utf8'),context);
const flush=()=>new Promise(resolve=>setImmediate(resolve));
const payload=(length)=>({summary:{length,lengthReliable:length!==null,speed:300,efficiency:97,stopSeconds:60},rows:[],date:'2026-10-22',label:'test'});
const respond=(request,data)=>request.resolve({ok:true,json:()=>Promise.resolve(data)});
(async()=>{
  assert.equal(pending.length,1);
  element('performance-window').listeners.change();assert.equal(pending.length,2);
  respond(pending[1],payload(67.22));await flush();assert.equal(element('performance-length').textContent,'67,22 m');
  respond(pending[0],payload(501.04));await flush();assert.equal(element('performance-length').textContent,'67,22 m','Old request must not replace the selected filter.');
  element('performance-window').listeners.change();assert.equal(element('performance-length').textContent,'—','Filter change must clear previous totals.');
  respond(pending[2],payload(null));await flush();assert.equal(element('performance-length').textContent,'—','Unknown production must not render as zero.');
  element('performance-window').listeners.change();pending[3].reject(new Error('database unavailable'));await flush();assert.equal(element('performance-length').textContent,'—','Failed fetch must not retain previous numbers.');
  assert.match(element('performance-results').innerHTML,/database unavailable/);
  console.log('Performance UI race, unknown-data and failure checks passed.');
})().catch(error=>{console.error(error);process.exitCode=1;});
