let pdfDoc = null;
let pageNum = savedPage;
let totalPages = 0;

const canvas = document.getElementById("pdfCanvas");
const ctx = canvas.getContext("2d");
const pageInfo = document.getElementById("pageInfo");
const paginaInput = document.getElementById("paginaInput");

//PDF.js restituisce un oggetto Promise che, una volta risolto, fornisce il PDF
pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
    pdfDoc = pdf;                       //memorizza oggetto PDF
    totalPages = pdf.numPages;          //salva numero di pagine
    renderPage(pageNum);                //disegna pagina corrente sul canvas
}).catch(err => {
    console.error("Errore caricamento PDF:", err);
});

function renderPage(num) {
    pdfDoc.getPage(num).then(page => {
        //scala della pagina
        const viewport = page.getViewport({ scale: 1.5 });

        canvas.height = viewport.height;
        canvas.width = viewport.width;
        //disegna pagina
        page.render({
            canvasContext: ctx,
            viewport: viewport
        });
        //aggiorna info pagina
        paginaInput.value = num;
        pageInfo.innerText = "Pagina " + num + " / " + totalPages;
    });
}

function nextPage() {
    if (pageNum < totalPages) {
        pageNum++;
        renderPage(pageNum);
    }
}

function prevPage() {
    if (pageNum > 1) {
        pageNum--;
        renderPage(pageNum);
    }
}