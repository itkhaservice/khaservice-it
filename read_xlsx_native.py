import zipfile
import xml.etree.ElementTree as ET
import os

def read_xlsx(file_path):
    try:
        with zipfile.ZipFile(file_path, 'r') as z:
            # 1. Read Shared Strings (the actual text content)
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                with z.open('xl/sharedStrings.xml') as f:
                    tree = ET.parse(f)
                    root = tree.getroot()
                    # Namespace map is usually needed, but let's try simple tag search
                    # The tag is usually {http://schemas.openxmlformats.org/spreadsheetml/2006/main}t
                    for t in root.iter():
                        if t.tag.endswith('t'):
                            shared_strings.append(t.text)

            print(f"--- Shared Strings Found: {len(shared_strings)} ---")
            # Print first 50 to guess content
            for s in shared_strings[:50]:
                print(s)
            
            # 2. List Sheets
            if 'xl/workbook.xml' in z.namelist():
                 with z.open('xl/workbook.xml') as f:
                    tree = ET.parse(f)
                    root = tree.getroot()
                    print("\n--- Sheets ---")
                    for sheet in root.iter():
                        if sheet.tag.endswith('sheet'):
                            print(f"Sheet: {sheet.attrib.get('name')}, ID: {sheet.attrib.get('sheetId')}")

    except Exception as e:
        print(f"Error: {e}")

read_xlsx('DA-data.xlsx')
