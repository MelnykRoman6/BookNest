let pdfDoc = null;
let pageNum = savedPage;
let totalPages = 0;

const canvas = document.getElementById("pdfCanvas");
const ctx = canvas.getContext("2d");
const pageInfo = document.getElementById("pageInfo");
const paginaInput = document.getElementById("paginaInput");

pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
    pdfDoc = pdf;
    totalPages = pdf.numPages;
    renderPage(pageNum);
}).catch(err => {
    console.error("Errore caricamento PDF:", err);
});

function renderPage(num) {

    pdfDoc.getPage(num).then(page => {

        const viewport = page.getViewport({ scale: 1.5 });

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        page.render({
            canvasContext: ctx,
            viewport: viewport
        });

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