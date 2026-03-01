/**
 * World Map — sanctuary info panel interaction
 * Requires global: SANCTUARY_DATA (injected by view)
 */

const buildings = document.querySelectorAll('.sanctuary-building');
const infoPanel = document.getElementById('info-panel');
const panelEmblem = document.getElementById('panel-emblem');
const panelTitle = document.getElementById('panel-title');
const panelDesc = document.getElementById('panel-desc');
const panelBtn = document.getElementById('panel-btn');
const closeBtn = document.getElementById('close-panel');

buildings.forEach(building => {
    building.addEventListener('click', () => {
        const key = building.dataset.sanctuary;
        const data = SANCTUARY_DATA[key];
        if (data) {
            panelEmblem.src = data.emblem;
            panelTitle.textContent = data.name;
            panelDesc.textContent = data.desc;
            panelBtn.href = data.url;
            panelBtn.style.display = data.url === '#' ? 'none' : 'inline-block';
            infoPanel.classList.add('active');
        }
    });
});

closeBtn.addEventListener('click', () => {
    infoPanel.classList.remove('active');
});

infoPanel.addEventListener('click', (e) => {
    if (e.target === infoPanel) infoPanel.classList.remove('active');
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && infoPanel.classList.contains('active')) {
        infoPanel.classList.remove('active');
    }
});
