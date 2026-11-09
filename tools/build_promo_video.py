from __future__ import annotations

import math
import subprocess
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "promotion"
FRAMES = OUT / "frames"
WIDTH, HEIGHT = 1920, 1080
FPS = 30

GREEN = "#075D50"
GREEN_2 = "#0E806B"
NAVY = "#10243E"
BLUE = "#2877B6"
AMBER = "#E5A52C"
RED = "#D34B4B"
INK = "#172033"
MUTED = "#657189"
BG = "#F3F6FA"
WHITE = "#FFFFFF"
LINE = "#DCE3EA"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    name = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / name), size)


def wrap(draw: ImageDraw.ImageDraw, text: str, fnt, max_width: int) -> list[str]:
    words, lines, line = text.split(), [], ""
    for word in words:
        candidate = f"{line} {word}".strip()
        if draw.textbbox((0, 0), candidate, font=fnt)[2] <= max_width:
            line = candidate
        else:
            if line:
                lines.append(line)
            line = word
    if line:
        lines.append(line)
    return lines


def rounded(draw, box, radius=18, fill=WHITE, outline=None, width=1):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def shell(title: str, subtitle: str, section: str, progress: float) -> tuple[Image.Image, ImageDraw.ImageDraw]:
    im = Image.new("RGB", (WIDTH, HEIGHT), BG)
    d = ImageDraw.Draw(im)
    d.rectangle((0, 0, WIDTH, 78), fill=NAVY)
    d.text((38, 22), "IT ASSET MANAGEMENT SYSTEM", font=font(28, True), fill=WHITE)
    d.rounded_rectangle((1570, 17, 1882, 60), 12, fill=GREEN_2)
    d.text((1602, 29), "ENTERPRISE EDITION", font=font(16, True), fill=WHITE)
    d.rectangle((0, 78, 265, HEIGHT), fill="#123D37")
    nav = ["Dashboard", "Asset Operations", "Master Data", "Reports", "User Management"]
    y = 124
    for item in nav:
        active = item == section
        if active:
            d.rounded_rectangle((14, y - 9, 250, y + 43), 10, fill=GREEN_2)
        d.ellipse((31, y + 4, 45, y + 18), fill=AMBER if active else "#89B9AE")
        d.text((58, y), item, font=font(20, active), fill=WHITE)
        y += 70
    d.text((306, 116), title, font=font(39, True), fill=INK)
    d.text((306, 166), subtitle, font=font(20), fill=MUTED)
    d.rectangle((306, 208, 1867, 211), fill=LINE)
    d.rectangle((0, HEIGHT - 8, int(WIDTH * progress), HEIGHT), fill=AMBER)
    return im, d


def metric(d, x, y, w, title, value, color):
    rounded(d, (x, y, x + w, y + 135), fill=WHITE, outline=LINE)
    d.rectangle((x, y, x + 7, y + 135), fill=color)
    d.text((x + 28, y + 23), title.upper(), font=font(16, True), fill=MUTED)
    d.text((x + 28, y + 55), value, font=font(42, True), fill=INK)


def caption(im, text):
    d = ImageDraw.Draw(im, "RGBA")
    d.rounded_rectangle((285, 960, 1890, 1044), 18, fill=(11, 27, 48, 232))
    f = font(25, True)
    lines = wrap(d, text, f, 1515)[:2]
    total = len(lines) * 31
    yy = 1001 - total // 2
    for line in lines:
        box = d.textbbox((0, 0), line, font=f)
        d.text((1087 - (box[2] - box[0]) / 2, yy), line, font=f, fill=WHITE)
        yy += 31


def title_card(title, subtitle, accent=GREEN):
    im = Image.new("RGB", (WIDTH, HEIGHT), NAVY)
    d = ImageDraw.Draw(im)
    for i in range(9):
        x = 1080 + i * 115
        d.ellipse((x, -90 + i * 55, x + 540, 450 + i * 55), outline="#173A59", width=3)
    d.rectangle((105, 158, 122, 830), fill=accent)
    title_font = font(72, True)
    title_y = 188
    d.multiline_text(
        (170, title_y),
        title,
        font=title_font,
        fill=WHITE,
        spacing=12,
    )
    title_box = d.multiline_textbbox(
        (170, title_y),
        title,
        font=title_font,
        spacing=12,
    )
    sf = font(34)
    # Position supporting copy below the complete multi-line heading.
    y = title_box[3] + 38
    for line in wrap(d, subtitle, sf, 810):
        d.text((174, y), line, font=sf, fill="#D9E8F1")
        y += 48
    d.rounded_rectangle((172, 580, 620, 648), 15, fill=accent)
    d.text((213, 596), "CONTACT FOR PRICING", font=font(25, True), fill=WHITE)
    d.text((174, 705), "irakibul550@gmail.com", font=font(28, True), fill="#F5C85C")
    d.text((174, 760), "For enterprise and manufacturing organizations", font=font(21), fill="#AFC2D2")
    return im


def dashboard_scene():
    im, d = shell("Management Dashboard", "Clear asset visibility for operational and management teams", "Dashboard", .12)
    xs = [306, 683, 1060, 1437]
    data = [("Total Assets", "5,248", BLUE), ("In Stock", "1,201", GREEN_2), ("Assigned", "3,657", GREEN), ("Warranty Alerts", "42", AMBER)]
    for x, item in zip(xs, data): metric(d, x, 250, 345, *item)
    rounded(d, (306, 420, 1092, 900), fill=WHITE, outline=LINE)
    d.text((338, 450), "Asset Distribution", font=font(25, True), fill=INK)
    bars = [("IT Equipment", 82, GREEN_2), ("Office Equipment", 64, BLUE), ("Network Devices", 50, AMBER), ("Machinery IT", 33, RED)]
    yy = 535
    for name, val, col in bars:
        d.text((345, yy), name, font=font(19), fill=INK)
        d.rounded_rectangle((570, yy + 4, 1000, yy + 29), 12, fill="#E7EDF2")
        d.rounded_rectangle((570, yy + 4, 570 + int(4.3 * val), yy + 29), 12, fill=col)
        d.text((1012, yy), f"{val}%", font=font(18, True), fill=MUTED)
        yy += 75
    rounded(d, (1120, 420, 1867, 900), fill=WHITE, outline=LINE)
    d.text((1152, 450), "Lifecycle Overview", font=font(25, True), fill=INK)
    center = (1360, 650); radius = 145
    d.ellipse((center[0]-radius, center[1]-radius, center[0]+radius, center[1]+radius), fill="#E8EDF1")
    d.arc((center[0]-radius, center[1]-radius, center[0]+radius, center[1]+radius), -90, 135, fill=GREEN_2, width=48)
    d.arc((center[0]-radius, center[1]-radius, center[0]+radius, center[1]+radius), 140, 235, fill=BLUE, width=48)
    d.arc((center[0]-radius, center[1]-radius, center[0]+radius, center[1]+radius), 240, 265, fill=AMBER, width=48)
    d.text((1305, 630), "5,248", font=font(36, True), fill=INK)
    d.text((1307, 674), "assets", font=font(20), fill=MUTED)
    legend = [("Assigned", GREEN_2), ("Available", BLUE), ("Attention", AMBER)]
    yy=540
    for label,col in legend:
        d.rounded_rectangle((1570,yy,1590,yy+20),5,fill=col)
        d.text((1605,yy-3),label,font=font(19),fill=INK); yy+=58
    caption(im, "Monitor stock, custody, returns, and warranty exposure from one dashboard.")
    return im


def entry_scene():
    im, d = shell("Asset Registration", "Receive individual assets or create an entire batch in one controlled entry", "Asset Operations", .24)
    rounded(d, (306, 250, 1867, 900), fill=WHITE, outline=LINE)
    d.text((345, 280), "Receipt Details", font=font(26, True), fill=GREEN)
    fields = [("Receipt / GRN Number", "GRN-5000034675"), ("Received Date", "13 Aug 2026"), ("Supplier / Vendor", "Enterprise Technology Supplier"), ("PO Number", "PO-4500038824"), ("Invoice Number", "INV-2026-0033"), ("Category", "Desktop Computer"), ("Quantity", "8 individual assets"), ("Location", "Main IT Store")]
    x0,y0=345,345
    for idx,(lab,val) in enumerate(fields):
        col=idx%2; row=idx//2; x=x0+col*735; y=y0+row*112
        d.text((x,y),lab,font=font(17,True),fill=MUTED)
        rounded(d,(x,y+28,x+675,y+78),8,fill="#F9FBFC",outline="#BFCBD7")
        d.text((x+16,y+42),val,font=font(20),fill=INK)
    d.rounded_rectangle((345, 812, 660, 867), 10, fill=GREEN_2)
    d.text((389, 826), "RECEIVE ASSETS", font=font(20, True), fill=WHITE)
    d.text((1038, 816), "Automatic asset numbers • Serial tracking • Upload evidence", font=font(18), fill=MUTED)
    caption(im, "Register one asset or a batch, capture procurement details, and generate unique asset numbers.")
    return im


def labels_scene():
    im, d = shell("QR Asset Labels", "Print single or bulk adhesive labels for fast identification", "Asset Operations", .35)
    d.text((330, 250), "Every serialized asset receives a scannable identity.", font=font(27, True), fill=INK)
    for i, code in enumerate(["DT0001", "LT0042", "MN0108"]):
        x=365+i*490
        rounded(d,(x,330,x+410,790),fill=WHITE,outline="#AAB6C2",radius=12)
        # deterministic QR-like pattern
        cell=12; qx=x+35; qy=380
        for yy in range(21):
            for xx in range(21):
                on=((xx*17+yy*11+i*7)%5 in (0,1)) or (xx<5 and yy<5) or (xx>15 and yy<5) or (xx<5 and yy>15)
                if on: d.rectangle((qx+xx*cell,qy+yy*cell,qx+(xx+1)*cell-1,qy+(yy+1)*cell-1),fill="#111")
        d.text((x+70,665),code,font=font(36,True),fill=INK)
        d.text((x+93,718),["Desktop","Laptop","Monitor"][i],font=font(21),fill=MUTED)
    d.rounded_rectangle((730, 835, 1440, 900), 12, fill=GREEN_2)
    d.text((805, 850), "70 × 35 mm THERMAL LABEL READY", font=font(24, True), fill=WHITE)
    caption(im, "Print one label or select many assets for bulk printing on an adhesive label printer.")
    return im


def assignment_scene():
    im, d = shell("Assignment & Custody", "Issue up to eight assets in one transaction with printable evidence", "Asset Operations", .48)
    rounded(d,(306,250,1867,900),fill=WHITE,outline=LINE)
    headers=[("Asset",330,220),("Serial Number",560,260),("Category",850,250),("Condition",1140,180),("Custodian",1350,250),("Status",1650,150)]
    d.rectangle((325,310,1848,365),fill=GREEN)
    for text_,x,w in headers:d.text((x,326),text_,font=font(17,True),fill=WHITE)
    rows=[("DT0001","SN-DT-2026-001","Desktop Computer","NEW","Employee 1042","ASSIGNED"),("MN0048","SN-MN-2026-048","Monitor","GOOD","Employee 1042","ASSIGNED"),("KM0182","SN-KM-2026-182","Keyboard / Mouse","NEW","Employee 1042","ASSIGNED")]
    yy=380
    for idx,row in enumerate(rows):
        d.rectangle((325,yy,1848,yy+92),fill="#F5FAF8" if idx%2==0 else WHITE)
        xs=[330,560,850,1140,1350,1650]
        for val,x in zip(row,xs):d.text((x,yy+30),val,font=font(18, val in ("NEW","GOOD","ASSIGNED")),fill=GREEN_2 if val=="ASSIGNED" else INK)
        yy+=92
    rounded(d,(325,690,900,845),fill="#EEF5FA",outline="#C8D8E6")
    d.text((355,718),"ISS-20260813-140216-786",font=font(24,True),fill=BLUE)
    d.text((355,760),"Assignment PDF • Receiver acceptance • Full audit trail",font=font(18),fill=MUTED)
    d.rounded_rectangle((1510,750,1810,812),12,fill=GREEN_2)
    d.text((1560,767),"ISSUE ASSETS",font=font(20,True),fill=WHITE)
    caption(im, "Create one controlled issuance batch, preserve custody, and generate assignment documentation.")
    return im


def return_scene():
    im,d=shell("Return Processing","Select eligible assets from an assignment batch and record each condition","Asset Operations",.60)
    rounded(d,(306,250,1867,900),fill=WHITE,outline=LINE)
    d.text((340,280),"Selected assignment: ISS-20260813-140216-786",font=font(24,True),fill=INK)
    items=[("DT0001","Good",GREEN_2),("MN0048","Fair",AMBER),("KM0182","Damaged",RED)]
    yy=355
    for asset,condition,col in items:
        rounded(d,(340,yy,1815,yy+115),fill="#F9FBFC",outline=LINE)
        d.text((375,yy+32),asset,font=font(25,True),fill=BLUE)
        d.text((600,yy+35),"Return condition",font=font(19),fill=MUTED)
        d.rounded_rectangle((820,yy+25,1105,yy+80),10,fill=col)
        d.text((880,yy+39),condition.upper(),font=font(19,True),fill=WHITE)
        d.text((1210,yy+35),"Condition updates the asset's current record",font=font(18),fill=INK)
        yy+=135
    d.text((350,785),"Return remarks",font=font(18,True),fill=MUTED)
    rounded(d,(350,815,1280,875),8,fill=WHITE,outline="#BFCBD7")
    d.text((370,832),"Checked and received by the IT store.",font=font(19),fill=INK)
    d.rounded_rectangle((1510,815,1815,875),12,fill=GREEN_2)
    d.text((1558,831),"COMPLETE RETURN",font=font(19,True),fill=WHITE)
    caption(im,"Return one or several assets together, record individual conditions, and restore stock automatically.")
    return im


def organization_scene():
    im,d=shell("Employees & Departments","Keep ownership and organizational custody clear","Master Data",.70)
    rounded(d,(306,250,1050,900),fill=WHITE,outline=LINE)
    d.text((340,280),"Employee Directory",font=font(25,True),fill=INK)
    rows=[("1042","Employee A","Information Technology","Engineer"),("1287","Employee B","Production","Supervisor"),("1510","Employee C","Finance","Analyst"),("1762","Employee D","Maintenance","Technician")]
    yy=350
    for eid,name,dept,pos in rows:
        d.ellipse((340,yy,392,yy+52),fill=GREEN_2)
        d.text((357,yy+14),name[-1],font=font(18,True),fill=WHITE)
        d.text((415,yy),f"{eid}  •  {name}",font=font(20,True),fill=INK)
        d.text((415,yy+30),f"{dept}  |  {pos}",font=font(17),fill=MUTED)
        yy+=120
    rounded(d,(1080,250,1867,900),fill=WHITE,outline=LINE)
    d.text((1115,280),"Custody Summary",font=font(25,True),fill=INK)
    metrics=[("Employees", "500+", BLUE), ("Departments", "Unlimited", GREEN_2), ("Assigned Assets", "3,657", AMBER)]
    yy=355
    for label,value,col in metrics:
        rounded(d,(1120,yy,1818,yy+125),fill="#F7FAFC",outline=LINE)
        d.rectangle((1120,yy,1128,yy+125),fill=col)
        d.text((1160,yy+20),label,font=font(19,True),fill=MUTED)
        d.text((1160,yy+52),value,font=font(34,True),fill=INK)
        yy+=155
    caption(im,"Connect assets to employees and departments so responsibility is always visible.")
    return im


def warranty_scene():
    im,d=shell("Warranty & Expiry Analytics","Identify expiring assets early and support renewal planning","Reports",.80)
    metric(d,306,250,350,"Under warranty","4,706",GREEN_2); metric(d,686,250,350,"Expiring in 30 days","42",AMBER); metric(d,1066,250,350,"Expired","187",RED); metric(d,1446,250,350,"No warranty","313",BLUE)
    rounded(d,(306,420,1867,900),fill=WHITE,outline=LINE)
    d.text((340,450),"Warranty Expiry by Month",font=font(25,True),fill=INK)
    vals=[35,55,42,78,62,105,88,130,95,72,48,30]
    base=830
    for i,v in enumerate(vals):
        x=370+i*118; h=v*2.4; col=RED if i<2 else AMBER if i<5 else GREEN_2
        d.rounded_rectangle((x,base-h,x+68,base),10,fill=col)
        d.text((x+13,base+12),["Aug","Sep","Oct","Nov","Dec","Jan","Feb","Mar","Apr","May","Jun","Jul"][i],font=font(16),fill=MUTED)
    caption(im,"Search and sort assets by warranty duration, with clear warning counts for upcoming expiry.")
    return im


def audit_scene():
    im,d=shell("Audit & Reporting","Every receipt, update, assignment, and return remains traceable","Reports",.90)
    rounded(d,(306,250,1867,900),fill=WHITE,outline=LINE)
    d.rectangle((325,290,1845,348),fill=GREEN)
    heads=[("Date & Time",340),("Event",590),("Asset",820),("Actor",1030),("Condition",1240),("Details",1460)]
    for h,x in heads:d.text((x,307),h,font=font(17,True),fill=WHITE)
    events=[("13 Aug 2026 09:12 AM","RECEIPT","DT0001","Administrator","NEW","Asset received into stock"),("13 Aug 2026 10:25 AM","ASSET UPDATE","DT0001","Administrator","NEW","Location and serial verified"),("13 Aug 2026 02:02 PM","ASSIGNMENT","DT0001","Issuer","NEW","Assigned to Employee 1042"),("18 Aug 2026 11:40 AM","RETURN","DT0001","Issuer","GOOD","Returned to Main IT Store")]
    yy=365
    colors={"RECEIPT":BLUE,"ASSET UPDATE":"#5A88A8","ASSIGNMENT":GREEN_2,"RETURN":AMBER}
    for i,row in enumerate(events):
        d.rectangle((325,yy,1845,yy+112),fill="#F5FAF8" if i%2==0 else WHITE)
        xs=[340,590,820,1030,1240,1460]
        for j,(val,x) in enumerate(zip(row,xs)):
            if j==1:
                d.rounded_rectangle((x,yy+31,x+170,yy+70),10,fill=colors[val]); d.text((x+14,yy+42),val,font=font(15,True),fill=WHITE)
            else:
                for li,line in enumerate(wrap(d,val,font(17,j in (2,4)),340 if j==5 else 195)[:2]):d.text((x,yy+30+li*25),line,font=font(17,j in (2,4)),fill=INK)
        yy+=112
    d.text((350,835),"Filter by event, category, department, date, asset number, and condition • Export professional reports",font=font(19,True),fill=MUTED)
    caption(im,"Maintain a complete audit trail and produce management-ready reports whenever required.")
    return im


def configuration_scene():
    im,d=shell("Configurable Modules","Enable only the capabilities each organization needs","User Management",.96)
    features=[("Approval Workflow","Department and IT manager approvals",False),("Bulk Label Printing","Single and multi-label thermal printing",True),("Accessory Inventory","Quantity-based cables and consumables",True),("Windows PC Agent","Automated hardware and software inventory",False),("Subscription & Vouchers","Optional multi-company operation",False)]
    yy=270
    for title,desc,on in features:
        rounded(d,(340,yy,1815,yy+105),fill=WHITE,outline=LINE)
        d.text((380,yy+20),title,font=font(22,True),fill=INK); d.text((780,yy+23),desc,font=font(18),fill=MUTED)
        col=GREEN_2 if on else "#9AA8B6"
        d.rounded_rectangle((1650,yy+28,1765,yy+74),23,fill=col)
        knob_x=1725 if on else 1658
        d.ellipse((knob_x,yy+32,knob_x+38,yy+70),fill=WHITE)
        d.text((1510,yy+39),"ENABLED" if on else "OPTIONAL",font=font(14,True),fill=col)
        yy+=122
    caption(im,"Optional modules can be enabled without changing the core asset registration, assignment, and return flow.")
    return im


def outro_scene():
    return title_card("READY TO MODERNIZE\nYOUR ASSET CONTROL?", "Centralize asset custody, simplify handovers, and give management reliable lifecycle visibility.", AMBER)


SCENES = [
    (title_card("IT ASSET\nMANAGEMENT SYSTEM", "A complete asset lifecycle platform for enterprise and manufacturing organizations."), 7),
    (dashboard_scene(), 9),
    (entry_scene(), 10),
    (labels_scene(), 8),
    (assignment_scene(), 10),
    (return_scene(), 10),
    (organization_scene(), 8),
    (warranty_scene(), 9),
    (audit_scene(), 10),
    (configuration_scene(), 8),
    (outro_scene(), 8),
]


NARRATION = """IT Asset Management System is a complete lifecycle platform designed for enterprise and manufacturing organizations.
Gain immediate visibility of available, assigned, returned, and warranty-sensitive assets from a centralized dashboard.
Register individual assets or receive an entire batch, capture procurement and location details, and generate unique asset numbers automatically.
Create scannable QR labels and print one or many seventy by thirty-five millimeter adhesive labels for fast physical identification.
Issue up to eight assets in one controlled transaction. Preserve employee or department custody and generate professional assignment documentation.
Process one or several returns from the same assignment batch, record the condition of every asset, and restore inventory automatically.
Maintain employee, department, and location master data so ownership and responsibility remain clear throughout the asset lifecycle.
Monitor warranty duration, identify upcoming expiry, and support renewal and replacement planning before equipment becomes a risk.
Every receipt, update, assignment, and return remains traceable through searchable audit history and management-ready reports.
Optional modules include configurable approval workflows, bulk label printing, quantity-based accessory inventory, a Windows inventory agent, and multi-company subscriptions.
Modernize your asset control with a secure and practical system built for growing organizations. Contact for pricing at irakibul five five zero at gmail dot com."""


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    FRAMES.mkdir(parents=True, exist_ok=True)
    for old in FRAMES.glob("*.png"):
        old.unlink()
    concat = []
    for i, (image, duration) in enumerate(SCENES):
        path = FRAMES / f"scene-{i:02}.png"
        image.save(path, quality=95)
        concat.append(f"file '{path.as_posix()}'\nduration {duration}\n")
    concat.append(f"file '{(FRAMES / f'scene-{len(SCENES)-1:02}.png').as_posix()}'\n")
    (OUT / "scenes.txt").write_text("".join(concat), encoding="utf-8")
    (OUT / "narration.txt").write_text(NARRATION, encoding="utf-8")
    (OUT / "youtube-description.txt").write_text(
        """IT Asset Management System — Complete Feature Demonstration\n\n"
        "A centralized solution for enterprise and manufacturing organizations to register, label, assign, return, monitor, and audit company assets.\n\n"
        "FEATURES DEMONSTRATED\n"
        "• Management dashboard and lifecycle analytics\n"
        "• Individual and batch asset registration\n"
        "• QR codes and 70 × 35 mm thermal labels\n"
        "• Employee and department assignment\n"
        "• Multi-asset return and condition tracking\n"
        "• Employee, department, vendor, location, unit, and category master data\n"
        "• Warranty expiry analytics\n"
        "• Complete audit and transaction reports\n"
        "• Optional approvals, accessories, Windows Agent, and subscription modules\n\n"
        "Contact for pricing and demonstration: irakibul550@gmail.com\n\n"
        "#ITAssetManagement #AssetTracking #InventoryManagement #ManufacturingTechnology #EnterpriseSoftware #DigitalTransformation #QRTracking""",
        encoding="utf-8",
    )
    ffmpeg = ROOT / "tools" / "video_deps" / "imageio_ffmpeg" / "binaries" / "ffmpeg-win-x86_64-v7.1.exe"
    silent = OUT / "it-asset-management-demo-silent.mp4"
    cmd = [str(ffmpeg), "-y", "-f", "concat", "-safe", "0", "-i", str(OUT / "scenes.txt"), "-vf", f"fps={FPS},format=yuv420p", "-c:v", "libx264", "-preset", "medium", "-crf", "19", "-movflags", "+faststart", str(silent)]
    subprocess.run(cmd, check=True)
    print(silent)


if __name__ == "__main__":
    main()
