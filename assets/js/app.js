function toggleSidebar() {

    const sidebar = document.querySelector('.sidebar');
    const navbar = document.querySelector('.navbar');
    const main = document.querySelector('.main-content');

    sidebar.classList.toggle('closed');
    navbar.classList.toggle('expanded');
    main.classList.toggle('expanded');

}
