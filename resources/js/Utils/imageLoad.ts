function isImgUrl(url) {
    return /\.(jpg|jpeg|svg|png|webp|avif|gif)$/.test(url)
}
const imageLoad = (url: string) => {
    //let imgIcon = new Image();
        const test = isImgUrl(url)
    if (test) {
        return 'https://cdn.ganhoubet.com/' + url;
    }

    return '/assets/img/arrow.svg'

}

export default imageLoad;
