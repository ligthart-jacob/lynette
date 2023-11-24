from os.path import splitext
from urllib.parse import unquote
import requests
from PIL import GifImagePlugin, Image
import hashlib
import sys

GifImagePlugin.LOADING_STRATEGY = (
    GifImagePlugin.LoadingStrategy.RGB_AFTER_DIFFERENT_PALETTE_ONLY
)


def rename(image):
    return hashlib.sha256(image.tobytes()).hexdigest()


def cropImage(image, extension):
    width, height = image.size
    croppedImage = image.crop((1, 1, width - 1, height - 1))
    croppedImage = croppedImage.resize((width, height))
    filename = rename(image)
    croppedImage.save(f"./../cards/{filename}{extension}")
    return f"/cards/{filename}{extension}"


def cropGif(image):
    duration = image.info["duration"]
    frames = []
    for index in range(0, image.n_frames):
        image.seek(index)
        frames.append(cropFrame(image))
    filename = rename(image)
    frames[0].save(
        f"./../cards/{filename}.gif",
        save_all=True,
        append_images=frames[1:],
        optimize=True,
        duration=duration,
        loop=0,
    )
    image.close()
    return f"/cards/{filename}.gif"


def cropFrame(frame):
    width, height = frame.size
    croppedFrame = frame.crop((2, 2, width - 2, height - 2))
    croppedFrame = croppedFrame.resize((width, height))
    return croppedFrame


def handleMedia(url):
    local = False if url.startswith("http") else True
    extension = sys.argv[2] if local else splitext(url)[1]
    with Image.open(url if local else requests.get(url, stream=True).raw) as media:
        if extension == ".gif":
            return cropGif(media)
        elif not local:
            return cropImage(media, extension)
        else:
            filename = rename(media)
            media.save(f"./../cards/{filename}{extension}")
            return f"/cards/{filename}{extension}"


def main():
    url = sys.argv[1].split("?")[0]
    filename = handleMedia(url)
    print(filename)


if __name__ == "__main__":
    main()
