const r="api/router.php";let c=0;document.addEventListener("DOMContentLoaded",()=>{p(),h(),f()});function f(){const e=document.getElementById("transferForm");e&&e.addEventListener("submit",y);const t=document.getElementById("recipient");if(t){let n;t.addEventListener("input",s=>{clearTimeout(n),n=setTimeout(()=>g(s.target.value),300)})}window.addEventListener("click",n=>{n.target.classList.contains("modal")&&E()})}async function p(){try{const t=await(await fetch(`${r}?action=get_balance`)).json();t.success?(c=t.balance,document.getElementById("gold-balance").textContent=t.balance.toLocaleString()):a(t.error||"Failed to load balance","error")}catch(e){console.error("Balance load error:",e),a("Network error while loading balance","error")}}async function h(){const e=document.getElementById("recent-transactions");try{const n=await(await fetch(`${r}?action=get_transactions&limit=5`)).json();n.success?n.transactions.length===0?e.innerHTML=`
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>No transactions yet</p>
                    </div>
                `:e.innerHTML=n.transactions.map(s=>v(s)).join(""):e.innerHTML=`<p class="error-text">${n.error}</p>`}catch(t){console.error("Transactions load error:",t),e.innerHTML='<p class="error-text">Failed to load transactions</p>'}}function v(e){const t=e.is_income,n=t?"fa-arrow-down":"fa-arrow-up",s=t?"income":"expense",o=t?"+":"",i=new Date(e.created_at),d=i.toLocaleDateString("id-ID",{day:"2-digit",month:"short",year:"numeric"}),m=i.toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"}),u={transfer:t?`From ${e.other_party}`:`To ${e.other_party}`,purchase:"Purchase",battle_reward:"Battle Reward",gacha:"Gacha Roll",shop:"Shop Purchase",sell_pet:"Pet Sale",daily_reward:"Daily Reward",admin_adjust:"Admin Adjustment",evolution:"Pet Evolution"}[e.type]||e.type;return`
        <div class="transaction-item">
            <div class="transaction-left">
                <div class="transaction-icon ${s}">
                    <i class="fas ${n}"></i>
                </div>
                <div class="transaction-info">
                    <div class="transaction-type">${u}</div>
                    <div class="transaction-description">${e.description||"-"}</div>
                </div>
            </div>
            <div class="transaction-right">
                <div class="transaction-amount ${s}">
                    ${o}${Math.abs(e.amount).toLocaleString()}
                    <i class="fas fa-coins" style="font-size: 0.8rem; margin-left: 3px;"></i>
                </div>
                <div class="transaction-date">${d} ${m}</div>
            </div>
        </div>
    `}async function g(e){const t=document.getElementById("searchResults");if(e.length<2){t.classList.remove("show");return}try{const s=await(await fetch(`${r}?action=search_nethera&query=${encodeURIComponent(e)}`)).json();s.success&&(s.results.length===0?t.innerHTML='<div class="search-result-item">No users found</div>':t.innerHTML=s.results.map(o=>`
                    <div class="search-result-item" onclick="selectRecipient('${o.username}', '${o.nama_lengkap}')">
                        <div class="result-username">@${o.username}</div>
                        <div class="result-name">${o.nama_lengkap}</div>
                    </div>
                `).join(""),t.classList.add("show"))}catch(n){console.error("Search error:",n)}}function y(e){e.preventDefault();const t=document.getElementById("recipient").value.trim(),n=parseInt(document.getElementById("amount").value),s=document.getElementById("description").value.trim()||"Gold transfer";if(!t){a("Please select a recipient","error");return}if(n<10){a("Minimum transfer amount is 10 gold","error");return}if(n>1e3){a("Maximum transfer amount is 1000 gold","error");return}if(n>c){a("Insufficient funds","error");return}document.getElementById("confirm-recipient").textContent=`@${t}`,document.getElementById("confirm-amount").textContent=`${n.toLocaleString()} gold`,document.getElementById("confirm-description").textContent=s,l(),L()}function l(){document.getElementById("transferModal").classList.remove("show"),document.getElementById("searchResults").classList.remove("show")}function L(){document.getElementById("confirmModal").classList.add("show")}function w(){document.getElementById("confirmModal").classList.remove("show")}function I(){document.getElementById("historyModal").classList.remove("show")}function E(){l(),w(),I()}function a(e,t="success"){const n=document.getElementById("toast");n.textContent=e,n.className=`toast ${t} show`,setTimeout(()=>{n.classList.remove("show")},3e3)}
//# sourceMappingURL=trapeza.js.map
