import sys
import MySQLdb
import codecs
import markdown
import os
from slugify import slugify
import json
import re

conn = MySQLdb.connect(host="localhost", user="rcc_caving", passwd="BX6yvOclelntsjyh", db="rcc_caving")
x = conn.cursor()

contentroot = 'backup/source/content/'

def replacer(match):
    halves = match.group().split('}(')
    first = halves[0].replace('{','')
    external = ""
    if first[0] == "!":
        external = "!"
        first= first[1:]
    
    align = first.split(' ')[-1:][0]
    
    caption = ' '.join(first.split(' ')[:-1])
    if caption:
        caption = caption[1:-1]
    else:
        caption = ""
    second = halves[1].replace(')','').split(',')
    if len(second) > 1:
        image = second[0].strip()
        url = second[1].strip()
    else:
        image = second[0]
        url = ""
    return '{{ ' + 'photo("{}","{}","{}","{}","{}")'.format(image,align,caption,external,url) + ' }}'

def clearTrips():
    query = """TRUNCATE TABLE rcc_caving.bolt_articles"""
    x.execute(query)
    query = """TRUNCATE TABLE rcc_caving.bolt_field_value"""
    x.execute(query)
    conn.commit()

def doArticles(subdir, subsite = ""):
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    md2 = markdown.Markdown()
    for root, dir, files in os.walk(
        contentroot + subdir + "/"
    ):
        for afile in files:
            if afile[-3:] != ".md":
                continue
            print(afile)
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                text = f.read()
                for index, line in enumerate(text.split('\n')):
                    if not line:
                        body = '\n'.join(text.split('\n')[index+1:]).replace("""'""", """''""")
                        break
                md.convert(text).replace("""'""", """''""")
                body = re.sub(
                    r"{{\s*DATE=(.*);\s*CAVE=([^;]*);?(|\d)\s*}}",
                    r'{{ people("\1","\2","\3") }}',
                    body,
                )
                body = re.sub(r'({.*}\(.*\))', replacer, body)
                body = re.sub(r"{{.*;.*}}", "", body)
                body = body.replace("mainimg", "mainimg()")
                body = body.replace("allpeople", "allpeople()")
                body = body.replace("photolink", "photolink()")
                #summary = (
                #    md2.convert(md.Meta["summary"][0]).replace("""'""", """''""")
                #    if "summary" in md.Meta
                #    else None
                #
                #)
                summary = md.Meta["summary"][0].replace("""'""", """''""") if "summary" in md.Meta else None
                status = md.Meta["status"][0] if "status" in md.Meta else "published"
                status = "published" if not status else status
                title = md.Meta["title"][0] if "title" in md.Meta else None
                date = md.Meta["date"][0] if "date" in md.Meta else None
                atype = md.Meta["type"][0] if "type" in md.Meta else None
                photoarchive = (
                    md.Meta["photoarchive"][0] if "photoarchive" in md.Meta else ""
                )
                mainimg = md.Meta["mainimg"][0] if "mainimg" in md.Meta else ""
                thumbl = md.Meta["thumbl"][0] if "thumbl" in md.Meta else ""
                thumbr = md.Meta["thumbr"][0] if "thumbr" in md.Meta else ""
                authors_raw = md.Meta["authors"][0] if "authors" in md.Meta else ""
                authors = [
                    author.strip()
                    for author in authors_raw.split(",")
                    if author.strip()
                ]
                authorids = []
                for author in authors:
                    query = (
                        """SELECT id FROM rcc_caving.bolt_cavers WHERE name='%s'"""
                        % author.replace("""'""", """''""")
                    )
                    x.execute(query)
                    print(query)
                    authorids.append(str(x.fetchall()[0][0]))
                location_raw = md.Meta["location"][0] if "location" in md.Meta else ""
                location = location_raw.strip().capitalize()
                #query = (
                #    """SELECT id FROM rcc_caving.bolt_locations WHERE name='%s'"""
                #    % location.capitalize()
                #)
                #print(query)
                #x.execute(query)
                #locationid = str(x.fetchall()[0][0])
                sql = """INSERT INTO rcc_caving.bolt_articles
    (slug, datecreated, datechanged, datepublish, ownerid, status, templatefields, title, summary, body, location, `date`, `type`, main_image, left_thumbnail, right_thumbnail, authors, photoarchive, subsite)
    VALUES('%s','%s','%s','%s',0,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', '%s');
    """
                print(afile)
                data = (
                    location + "-" + date,
                    date + " 00:00",
                    date + " 00:00",
                    date + " 00:00",
                    status,
                    "[]",
                    title,
                    summary,
                    body,
                    location,
                    date,
                    atype,
                    mainimg,
                    thumbl,
                    thumbr,
                    json.dumps(authorids),
                    photoarchive,
                    subsite
                )
                query = sql % data
                x.execute(query)
                articleid = x.lastrowid
                cavepeeps = md.Meta["cavepeeps"] if "cavepeeps" in md.Meta else []
                for (index, cavepeep) in enumerate(cavepeeps):
                    datas = [data for data in cavepeep.split(";") if data]
                    for data in datas:
                        parts = data.split("=")
                        if parts[0].strip() == "DATE":
                            sql = """INSERT INTO rcc_caving.bolt_field_value
    (contenttype, content_id, name, `grouping`, fieldname, fieldtype, value_date, value_json_array)
    VALUES('articles', %s, 'cavepeeps', %s, '%s', '%s', '%s', '%s');"""
                            data = (articleid, index, "Date", "date", parts[1], "")
                        elif parts[0].strip() == "CAVE":
                            caves = [cave.strip() for cave in parts[1].split(">")]
                            caveids = []
                            for cave in caves:
                                query = (
                                    """SELECT id FROM rcc_caving.bolt_caves WHERE name='%s'"""
                                    % cave.replace("""'""", """''""")
                                )
                                x.execute(query)
                                caveids.append(str(x.fetchall()[0][0]))
                            sql = """INSERT INTO rcc_caving.bolt_field_value
    (contenttype, content_id, name, `grouping`, fieldname, fieldtype, value_date, value_json_array)
    VALUES('articles', %s, 'cavepeeps', %s, '%s', '%s', '%s', '%s');"""
                            data = (
                                articleid,
                                index,
                                "Cave",
                                "selectmultiple",
                                "",
                                json.dumps(caveids),
                            )
                        elif parts[0].strip() == "PEOPLE":
                            people = [person.strip() for person in parts[1].split(",")]
                            peopleids = []
                            for person in people:
                                query = (
                                    """SELECT id FROM rcc_caving.bolt_cavers WHERE name='%s'"""
                                    % person.replace("""'""", """''""")
                                )
                                x.execute(query)
                                peopleids.append(str(x.fetchall()[0][0]))
                            sql = """INSERT INTO rcc_caving.bolt_field_value
    (contenttype, content_id, name, `grouping`, fieldname, fieldtype, value_date, value_json_array)
    VALUES('articles', %s, 'cavepeeps', %s, '%s', '%s', '%s', '%s');"""
                            data = (
                                articleid,
                                index,
                                "People",
                                "selectmultiple",
                                "",
                                json.dumps(peopleids),
                            )
                        elif parts[0].strip() == "NOCAVE":
                            caves = ["NOCAVE"]
                            caveids = []
                            for cave in caves:
                                query = (
                                    """SELECT id FROM rcc_caving.bolt_caves WHERE name='%s'"""
                                    % cave.replace("""'""", """''""")
                                )
                                x.execute(query)
                                caveids.append(str(x.fetchall()[0][0]))
                            sql = """INSERT INTO rcc_caving.bolt_field_value
    (contenttype, content_id, name, `grouping`, fieldname, fieldtype, value_date, value_json_array)
    VALUES('articles', %s, 'cavepeeps', %s, '%s', '%s', '%s', '%s');"""
                            data = (
                                articleid,
                                index,
                                "Cave",
                                "selectmultiple",
                                "",
                                json.dumps(caveids),
                            )
                            print(cavepeep)
                            query = sql % data
                            print(query)
                            x.execute(query)
                            people = [person.strip() for person in parts[1].split(",")]
                            peopleids = []
                            for person in people:
                                query = (
                                    """SELECT id FROM rcc_caving.bolt_cavers WHERE name='%s'"""
                                    % person.replace("""'""", """''""")
                                )
                                x.execute(query)
                                peopleids.append(str(x.fetchall()[0][0]))
                            sql = """INSERT INTO rcc_caving.bolt_field_value
    (contenttype, content_id, name, `grouping`, fieldname, fieldtype, value_date, value_json_array)
    VALUES('articles', %s, 'cavepeeps', %s, '%s', '%s', '%s', '%s');"""
                            data = (
                                articleid,
                                index,
                                "People",
                                "selectmultiple",
                                "",
                                json.dumps(peopleids),
                            )
                            print(cavepeep)
                            query = sql % data
                            print(query)
                            x.execute(query)
                        if parts[0].strip() != "NOCAVE":
                            print(cavepeep)
                            query = sql % data
                            print(query)
                            x.execute(query)

                conn.commit()


def doIndex():
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    for root, dir, files in os.walk("../source/content/index/"):
        for afile in files:
            if afile[-3:] != ".md":
                continue
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                text = f.read()
                for index, line in enumerate(text.split('\n')):
                    if not line:
                        body = '\n'.join(text.split('\n')[index+1:]).replace("""'""", """''""")
                        break
                md.convert(text).replace("""'""", """''""")
                status = md.Meta["status"][0] if "status" in md.Meta else "published"
                status = "published" if not status else status
                title = md.Meta["title"][0] if "title" in md.Meta else None
                date = md.Meta["date"][0] if "date" in md.Meta else None
                atype = md.Meta["type"][0] if "type" in md.Meta else None
                photoarchive = (
                    md.Meta["photoarchive"][0] if "photoarchive" in md.Meta else ""
                )
                thumbl = md.Meta["thumbl"][0] if "thumbl" in md.Meta else ""
                thumbr = md.Meta["thumbr"][0] if "thumbr" in md.Meta else ""
                link = md.Meta["link"][0] if "link" in md.Meta else ""
                linktext = md.Meta["linktext"][0] if "linktext" in md.Meta else ""
                location_raw = md.Meta["location"][0] if "location" in md.Meta else ""
                location = location_raw.strip()
                query = (
                    """SELECT id FROM rcc_caving.bolt_locations WHERE name='%s'"""
                    % "Index"
                )
                x.execute(query)
                sql = """INSERT INTO rcc_caving.bolt_articles
    (slug, datecreated, datechanged, datepublish, ownerid, status, templatefields, title, summary, body, location, `date`, `type`, main_image, left_thumbnail, right_thumbnail, photoarchive,linktext,linkhref)
    VALUES('%s','%s','%s','%s',0,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');
    """
                data = (
                    (title + "-" + date).lower().replace(" ", "-"),
                    date + " 00:00",
                    date + " 00:00",
                    date + " 00:00",
                    status,
                    "[]",
                    title,
                    body,
                    "",
                    location,
                    date,
                    atype,
                    "",
                    thumbl,
                    thumbr,
                    photoarchive,
                    linktext,
                    link
                )
                query = sql % data
                print(query)
                x.execute(query)
                conn.commit()

def clearPages():
    query = """TRUNCATE TABLE rcc_caving.bolt_pages"""
    x.execute(query)

def doPages(subdir, subsite=""):
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    for root, dir, files in os.walk(contentroot + subdir + "/"):
        for afile in files:
            if afile[-3:] != ".md":
                continue
            print(afile)
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                text = f.read()
                for index, line in enumerate(text.split('\n')):
                    if not line:
                        body = '\n'.join(text.split('\n')[index+1:]).replace("""'""", """''""")
                        break
                md.convert(text).replace("""'""", """''""")
                body = re.sub(
                    r"{{\s*DATE=(.*);\s*CAVE=([^;]*);?(\d)?\s*}}",
                    r'{{ people("\1","\2","\3") }}',
                    body,
                )
                body = re.sub(r'({.*}\(.*\))', replacer, body)
                body = re.sub(r"{{.*;.*}}", "", body)
                body = body.replace("mainimg", "mainimg()")
                body = body.replace("allpeople", "allpeople()")
                body = body.replace("photolink", "photolink()")
                title = md.Meta["title"][0] if "title" in md.Meta else None
                slug = afile[:-3]
                date = "2000-01-01"
                sql = """INSERT INTO rcc_caving.bolt_pages
    (slug, datecreated, datechanged, datepublish, ownerid, status, templatefields, title, body, subsite)
    VALUES('%s','%s','%s','%s',0,'%s','%s','%s','%s','%s');
    """
                data = (
                    slug,
                    date + " 00:00",
                    date + " 00:00",
                    date + " 00:00",
                    "published",
                    "[]",
                    title,
                    body,
                    subsite
                )
                query = sql % data
                print(query)
                x.execute(query)
                conn.commit()


def doCaves():
    query = """TRUNCATE TABLE rcc_caving.bolt_caves"""
    x.execute(query)
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    for root, dir, files in os.walk("../source/content/caves"):
        for afile in files:
            if afile[-3:] != ".md":
                continue
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                name = afile[:-3].replace("""'""", """''""")
                text = f.read()
                for index, line in enumerate(text.split('\n')):
                    if not line:
                        body = '\n'.join(text.split('\n')[index+1:]).replace("""'""", """''""")
                        break
                md.convert(text)
                status = "published"
                country = (
                    md.Meta["country"][0].replace("""'""", """''""")
                    if "country" in md.Meta
                    else ""
                )
                region = (
                    md.Meta["region"][0].replace("""'""", """''""")
                    if "region" in md.Meta
                    else ""
                )
                subregion = (
                    md.Meta["subregion"][0].replace("""'""", """''""")
                    if "subregion" in md.Meta
                    else ""
                )
                system = (
                    md.Meta["system"][0].replace("""'""", """''""")
                    if "system" in md.Meta
                    else ""
                )
                location = (
                    md.Meta["location"][0].replace("""'""", """''""")
                    if "location" in md.Meta
                    else ""
                )
                query = """SELECT id FROM rcc_caving.bolt_caves WHERE name='%s'""" % name
                x.execute(query)
                result = x.fetchall()
                if result:
                    continue
                sql = """INSERT INTO rcc_caving.bolt_caves
(slug, ownerid, status, name, body, country, region, subregion, `system`, location)
VALUES('%s', 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
"""
                data = (
                    name.lower()
                    .replace(" ", "-")
                    .replace("""''""", "-")
                    .replace("""'""", "-"),
                    status,
                    name,
                    body,
                    country,
                    region,
                    subregion,
                    system,
                    location,
                )
                query = sql % data
                print(sql % data)
                x.execute(query)
                conn.commit()
    sql = """INSERT INTO rcc_caving.bolt_caves
(slug, ownerid, status, name, body, country, region, subregion, `system`, location)
VALUES('%s', 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
"""
    data = (
        "NOCAVE",
        "published",
        "NOCAVE",
        "",
        "",
        "",
        "",
        "",
        "",
        )
    query = sql % data
    print(sql % data)
    x.execute(query)
    conn.commit()

def clearLocations():
    query = """TRUNCATE TABLE rcc_caving.bolt_locations"""
    x.execute(query)

def doLocations(subdir):
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    for root, dir, files in os.walk("../source/content/" + subdir + "/"):
        for afile in files:
            if afile[-3:] != ".md":
                continue
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                md.convert(f.read())
                status = "published"
                location = (
                    md.Meta["location"][0].capitalize()
                    if "location" in md.Meta
                    else None
                )
                if location is None:
                    continue
                query = """SELECT id FROM rcc_caving.bolt_locations WHERE name='%s'""" % location
                x.execute(query)
                result = x.fetchall()
                if result:
                    continue
                sql = """INSERT INTO rcc_caving.bolt_locations
(ownerid, status, name)
VALUES(0, 'published', '%s');
"""
                data = (
                    location
                )
                query = sql % data
                print(sql % data)
                x.execute(query)
                conn.commit()
    query = (
        """SELECT id FROM rcc_caving.bolt_locations WHERE name='%s'"""
        % "Index"
    )
    x.execute(query)
    print(query)
    result = x.fetchall()
    if result:
        return
    sql = """INSERT INTO rcc_caving.bolt_locations
(ownerid, status, name)
VALUES(0, 'published', '%s');
"""
    data = (
         "Index"
       )
    query = sql % data
    print(sql % data)
    x.execute(query)
    conn.commit()

def doCavers():
    cavers = [
        "Adam",
        "Adam Dobson",
        "Adriel Clark",
        "Ah Guan",
        "Ahmed Saad",
        "Ai Li Cho",
        "Aisha",
        "Alec Bluman",
        "Aleeza Janmohamed",
        "Alex Betts",
        "Alex Herriott",
        "Alex Seaton",
        "Alexandre Jeannier",
        "Alice Brown",
        "Aliette Boshier",
        "Aljosa",
        "Aljošha",
        "Alvin Ang",
        "Alvin Chow",
        "Ambia Begum",
        "Andrej",
        "Andrew Booker",
        "Andrew Wilkins",
        "Andrzej Dziadkowiec",
        "Andy Jurd",
        "Anne VDP",
        "Annie Yiu",
        "Ari Whitby",
        "Arianna Renzini",
        "Arun",
        "Arun Paul",
        "Ashley Stearn",
        "Ash Stearn",
        "Augustinas Prusokas",
        "Barbora Pinlova",
        "Becky Dykes",
        "Bela",
        "Ben Banfield",
        "Ben Honan",
        "Ben O",
        "Ben Ogborne",
        "Ben Richards",
        "Ben S",
        "Ben Zelenay",
        "Bernard",
        "Bhavik Lodhia",
        "Bhavnisha",
        "Bogdan Galilo",
        "Boris",
        "Bruce Kawa",
        "Carla Huynh",
        "Caroline Ainsworth",
        "Carrie",
        "Carrie Chen",
        "Catalina Garcia",
        "Catherine Claudet",
        "Cecilia Kan",
        "Celia Tinsley",
        "Charles Collicutt",
        "Charlmane Lun",
        "Charlotte Garner",
        "Chelsea Lefever",
        "Chin Guo Heng",
        "Ching Sik",
        "Chris",
        "Chris Bradley",
        "Christopher Bradley",
        "Chris Dillon",
        "Chris Keeley",
        "Chris McDonnell",
        "Chris Rogers",
        "Christian Franke",
        "Christina",
        "Christina Picken",
        "Christoph Aymanns",
        "Clara Rodríguez Fernández",
        "Clare Tan",
        "Clement Stahl",
        "Clement Tremblay",
        "Clewin Griffith",
        "Clewin Griffiths",
        "Clinton Chan",
        "Clive Westlake",
        "Colm Carroll",
        "Constantinos Lordos",
        "Dan Cooke",
        "Dan Greenwald",
        "Daniel Tio",
        "Daniel Zacki",
        "Daniella McManamon",
        "Darryl Anderson",
        "Dave",
        "Dave Kirkpatrick",
        "David Kirkpatrick",
        "Dave Loeffler",
        "Dave Wilson",
        "David Ciecierski",
        "David Wilson",
        "David Yu",
        "Dean Cartwright",
        "Deb",
        "Denis Langlois",
        "Devansh Agarwal",
        "Dominic Tan",
        "Ed Murfitt",
        "Edith Huebner",
        "Edmund",
        "Elen Newcombe",
        "Elijah Choi",
        "Elizabeth Ellison",
        "Emily Chow",
        "Eric Seidman",
        "Erik",
        "Felicia Burtscher",
        "Finbarr Fallon",
        "Fiona Hartley",
        "Florian Strub",
        "Floris Wu",
        "Fratnik",
        "GOF",
        "Gabi Sonnet",
        "Gabriel Cher",
        "Gaby Sonnet",
        "George Taylor",
        "Georgia Kouti",
        "Gerard Dericks",
        "Gerardo",
        "Gerardo Ocana-Fuentes",
        "Gergely Ambrus",
        "Giulio Deganutti",
        "Goaty",
        "Gokhan Tut",
        "Gosia Bugalska",
        "Guarav Bhutani",
        "Guia",
        "Hannah",
        "Hannah Heyemann",
        "Haviera",
        "Hayley Arthurs",
        "Helen Jones",
        "Hengxi Ouyang",
        "Ho Yan Jin",
        "Hugh Penney",
        "Hugo",
        "Ian Ashworth",
        "Ingrid Nojoumian",
        "Isabelle Grenville",
        "Isha Kaur",
        "Ivana Fertitta",
        "Izi",
        "Jack Garrett-Jones",
        "Jack Halliday",
        "Jack Hare",
        "Jack Hare's Dad",
        "Jack Jones",
        "Jackson Bong",
        "Jacob Puhalo-Smith",
        "Jake Reynalds",
        "Jake Thorne",
        "James",
        "James Berg",
        "James Cowley",
        "James Huggett",
        "James Kirkpatrick",
        "James Nichols",
        "James O'Hanlon",
        "James Perry",
        "James Roberts",
        "James Wilson",
        "Jamie Perrelet",
        "Jan Evetts",
        "Jana Carga",
        "Janet Cotter",
        "Jani",
        "Jarvist Frost",
        "Javier Maurino",
        "Jay",
        "Jay Chen",
        "Jean Maillard",
        "Jean-Yves Burlet",
        "Jeannie Michaels",
        "Jennifer Ryder",
        "Jerome",
        "Jerry",
        "Jesse Zondervan",
        "Jessica Wunder",
        "Jia Cheong",
        "Jim Evans",
        "Jim Lee",
        "Jim Li",
        "Jingzhi An",
        "Jipeng Su",
        "Jo King",
        "Jo-Kuang",
        "Joachim",
        "Joe King",
        "Johannes Karges",
        "John Walker",
        "Johnny Colburn",
        "Jonny",
        "Jonny Hardman",
        "Joshua Marcinkowski",
        "Joshua Newington",
        "Julian Keenan",
        "Jutta Schnabel",
        "Karim Elbakary",
        "Kat",
        "Kat Hawkins",
        "Kate Smith",
        "Katherine Mushi",
        "Katy Morgan",
        "Kee Zhiyin",
        "Kelvin Choi",
        "Kenneth Bok Chek Kwong",
        "Kenneth Tan",
        "Kev",
        "Kevin Li",
        "Kletnik",
        "Kong",
        "Kong You Liow",
        "Konrad Domanski",
        "Kos",
        "Larry",
        "Larry Jiang",
        "Laura Harrison",
        "Laura Petrescu",
        "Laurence Moran",
        "Layla Aston",
        "Layla Wang",
        "Leo Carlin",
        "Lester Loi",
        "Lester Loy",
        "Liam Barden",
        "Liam Johnstone",
        "Liane Tan",
        "Liew Mei Hui",
        "Lim Kangyu",
        "Loh Kai Li",
        "Lorna Watson",
        "Lorraine Embleton",
        "Louise Ranken",
        "Lucien Halada",
        "Ludo Lewicki-Garreau",
        "Luis Ayala",
        "Luke Williams",
        "Lyndon Leggate",
        "Magdaleena Gocek",
        "Marc Labuhn",
        "Marian Zastawny",
        "Marion Ziller",
        "Mark",
        "Mark Gee",
        "Martin Anderson",
        "Martin K",
        "Martin Kowalski",
        "Martin McGowan",
        "Mathew Kibble",
        "Matt",
        "Maud Barthelemy",
        "Maud Langlois",
        "Maver",
        "Max Hörmann",
        "May Law",
        "Mehdi Ben Slama",
        "Melanie Singh",
        "Michelle Ghodrat",
        "Mihails Delmans",
        "Mike Foley",
        "Mikhail Soloviev",
        "Mikhails Delmans",
        "Miriam North-Ridao",
        "Misty Haith",
        "Mo",
        "Moritx Guenther",
        "Moritz Guenther",
        "Mustafa",
        "Myles Denton",
        "Nadine Kalmoni",
        "Natalie Whittingham",
        "Nathan Daniels",
        "Nathaniel Oshunniyi",
        "Nava Schwarzgold",
        "Neel Savani",
        "Nia John",
        "Nicholas Loh",
        "Nick",
        "Nick Koukoulekidis",
        "Nicola Crowhurst",
        "Nicola McCallion",
        "Niko Kral",
        "Noah Smith",
        "Nuria Devos",
        "Oded Kutok",
        "Oliver Myerscough",
        "Olle Akesson",
        "Otter",
        "Paavo",
        "Paul",
        "Paul Hutton",
        "Pavel Kroupa",
        "Pella Frost",
        "Pete Hambly",
        "Pete Jurd",
        "Pete Mansbridge",
        "Peter Fenton",
        "Peter Fishbasher",
        "Peter Ganson",
        "Petros Christofides",
        "Philipp",
        "Phillipe",
        "Pierre Blacque",
        "Pip Crosby",
        "Quiet Tim",
        "Rachel Marx",
        "Rahel",
        "Ramon Winterhalder",
        "Rayson Ng",
        "Rebecca Diss",
        "Rebecca Dykes",
        "Rhys Tyers",
        "Richard Evans",
        "Rick",
        "Rik Venn",
        "Rita Borg",
        "Rong Kai",
        "Rosalind O'Driscoll",
        "Rosanna Nichols",
        "Rozzie O'Driscoll",
        "Ryan Boultbee",
        "Ryan Clark",
        "Saber King",
        "Sally Dacie",
        "Sam",
        "Sam (Rozzie)",
        "Sam Lacey",
        "Sam Lee",
        "Sam Page",
        "Sam Yen Shuang",
        "Sammy",
        "Sandeep Mavadia",
        "Sarah",
        "Sarah Arctic",
        "Sarah Gian",
        "Sarah Zylinski",
        "Sean Peezick",
        "Sebastian Mason",
        "Seán",
        "Shaun Kong",
        "Shed",
        "Shivani Gangadia",
        "Simon Ang",
        "Simon Nouis",
        "Someone",
        "Sophie Larnaudie",
        "Sophie Musset",
        "Spela",
        "Ste",
        "Stefan Bennewitz",
        "Stephanie Ford",
        "Stephanie Klecha",
        "Steven Pool",
        "Su Teh",
        "Tamzin Zawadska",
        "Tanguy Racine",
        "Teh Ming Hwang",
        "Tetley",
        "Thalia Konaris",
        "Thara Supasiti",
        "Thomas Aghulon",
        "Thomas McCarthy-Ward",
        "Thomas Porter",
        "Tilly Nielsen-Earle",
        "Tim Child",
        "Tim Osborne",
        "Tim Palmer",
        "Tom Ayles",
        "Tom Barker",
        "Tom Batho",
        "Tom Brown",
        "Tom Hamant",
        "Tom Jenkins",
        "Tom Porter",
        "Tomas Satura",
        "Tomasz",
        "Tony Seddon",
        "Tony Wu",
        "Trev",
        "Tunvez Boulic",
        "Una Barker",
        "Venoshah Mahkeydron",
        "Veronique Mahue",
        "Victor",
        "Wesley Gaunt",
        "Will French",
        "Will Norwood",
        "Will Scott",
        "William French",
        "Xiao Ting",
        "Xiaoming Liu",
        "Yu Kang",
        "Yuqi Wang",
        "Yvonne",
        "Zhen Lim",
        "Zirui Xu",
        "Zoe Young",
        "Zoja Nagurnaja",
        "Marinette",
        "Skippy",
        "Alex Lipp",
        "Steven",
        "Max Stunt",
        "Zaeem Najeeb",
        "Sam Holden",
        "Rishil",
        "Lucie",
        "Isobel",
        "Hugo",
        "Armand Cadet",
        "Andreas",
        "Solomon Roach",
        "Nic Gruse",
        "Ignacy Bartnik",
        "Carl Hentes",
        "Ana Teck",
        "Shaheer Abdul Rahman",
        "Ryan",
        "Rishil Patel",
        "Rhuarhri Cordon",
        "Peter Fenton",
        "Meera Gautami",
        "Louise Ranken",
        "Lorna Watson",
        "Leia Chun",
        "Laura Harrison",
        "Joe Garvey",
        "Ivan",
        "Isobel Wood",
        "Hugo Poissonnier",
        "Henry",
        "Frank Kong",
        "Beat Zurbuchen",
        "Angela",
        "Xanthe Hatchwell",
        "William Kerley",
        "Rush",
        "Gionieva Fraser",
        "Charles",
        "Carl Hentges",
        "Andreas Revorta",
        "Malcolm Barr",
        "Jim Briggs",
        "Chris Backhouse",
        "Simon Seward",
        "Phil Hay",
        "Rob Chaddock",
        "Mark Evans",
        "Ian Mckenna",
        "Dave Mountain",
        "Chris Birkhead",
        "Harry Locke",
        "ICCC"
    ]
    query = """TRUNCATE TABLE rcc_caving.bolt_cavers"""
    x.execute(query)
    for caver in cavers:
        sql = """INSERT INTO rcc_caving.bolt_cavers
(slug, ownerid, status, name, summary, body)
VALUES('%s', 0, 'published', '%s', '', '');"""
        data = (slugify(caver), caver.replace("""'""", """''"""))
        query = sql % data
        print(sql % data)
        x.execute(query)
        conn.commit()

def convertCamel(name, divider):
    s1 = re.sub('(.)([A-Z][a-z]+)', r'\1' + divider + r'\2', name)
    return re.sub('([a-z0-9])([A-Z])',r'\1' + divider + r'\2', s1)

def doWiki():
    query = """TRUNCATE TABLE rcc_caving.bolt_wiki"""
    x.execute(query)
    md = markdown.Markdown(extensions=["markdown.extensions.meta"])
    for root, dir, files in os.walk("../source/content/wiki"):
        for afile in files:
            if afile[-3:] != ".md" or afile == '_Footer.md':
                continue
            with codecs.open(root + "/" + afile, "r", "utf-8") as f:
                text = f.read()
                for index, line in enumerate(text.split('\n')):
                    if not line:
                        body = '\n'.join(text.split('\n')[index+1:]).replace("""'""", """''""")
                        break
                md.convert(text).replace("""'""", """''""")
                body = re.sub(
                    r"{{\s*DATE=(.*);\s*CAVE=([^;]*);?(\d)?\s*}}",
                    r'{{ people("\1","\2","\3") }}',
                    body,
                )
                body = re.sub(
                    r'{(|!)(|".*")\s*(\w*)\s*}\(([^),]*)\s*,?\s*(|[^)]*)\)',
                    r'{{ photo("\4","\3","\2","\1","\5") }}',
                    body,
                )
                body = re.sub(r"{{.*;.*}}", "", body)
                body = body.replace("mainimg", "mainimg()")
                body = body.replace("allpeople", "allpeople()")
                body = body.replace("photolink", "photolink()")
                title = convertCamel(afile[:-3], ' ')
                path = md.Meta["path"][0] if "path" in md.Meta else ''
                slug = convertCamel(afile[:-3], '-').lower()
                print("'" + slug + "'")
                sql = """INSERT INTO rcc_caving.bolt_wiki
    (slug, datecreated, datechanged, datepublish, ownerid, status, templatefields, title, body, path)
    VALUES('%s','%s','%s','%s',0,'%s','%s','%s','%s','%s');
    """
                data = (
                    slug,
                    "01-01-2019 00:00",
                    "01-01-2019 00:00",
                    "01-01-2019 00:00",
                    "published",
                    "[]",
                    title,
                    body,
                    path
                )
                query = sql % data
                #print(query)
                x.execute(query)
                conn.commit()

#doCaves()
#doCavers()
#clearLocations()
#doLocations("trip")
#doLocations("tour")
clearTrips()
doArticles("trip")
doArticles("tour")
doArticles("_slovenia/articles", 'Slovenia')
print('aaa')
#doIndex()
#clearPages()
#doPages("pages")
#doPages("_slovenia/pages", "Slovenia")
#doWiki()

conn.close()

sys.exit()
