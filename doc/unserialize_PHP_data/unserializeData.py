# unserializeData.py
################################################################################
# Example: Python unserialize PHP data
# 28.09.2025
# /home/AhoyDTU/ahoyenv/bin/python3 -m pip install phpserialize
# https://remarkablemark.org/blog/2020/09/26/python-phpserialize/
################################################################################
from phpserialize import unserialize, phpobject

byte_data   = b'a:1:{s:3:"byt";s:3:"bar";}'
string_data =  'a:1:{s:3:"str";s:3:"see";}'
binary_data = string_data.encode()
binary_data = bytes(string_data, 'utf-8')

unserialize_data_b = unserialize(byte_data)
unserialize_data_s = unserialize(binary_data)

print (f"IN:  {type(byte_data)=} - {len(byte_data)=} - {byte_data=}")
print (f"IN:  {type(string_data)=} - {len(string_data)=} - {string_data=}")
print (f"OUT: {type(unserialize_data_b)=} - {len(unserialize_data_b)=} - {unserialize_data_b=}")
print (f"OUT: {type(unserialize_data_s)=} - {len(unserialize_data_s)=} - {unserialize_data_s=}")



byte_data   = b'O:8:"stdClass":1:{s:5:"b_obj";s:3:"qux";}'
string_data =  'O:8:"stdClass":1:{s:5:"s_obj";s:3:"see";}'

binary_data = string_data.encode('utf-8')
#binary_data = bytes(string_data, 'utf-8')

unserialize_data_b = unserialize(byte_data,   object_hook=phpobject)
unserialize_data_s = unserialize(binary_data, object_hook=phpobject)

output_b = unserialize_data_b._asdict()
b_output = {
    key.decode(): val.decode() if isinstance(val, bytes) else val
    for key, val in output_b.items()
}

output_s = unserialize_data_s._asdict()
s_output = {
    key.decode(): val.decode() if isinstance(val, bytes) else val
    for key, val in output_s.items()
}

print (f"IN:  {type(byte_data)=} - {len(byte_data)=} - {byte_data=}")
print (f"IN:  {type(string_data)=} - {len(string_data)=} - {string_data=}")
print (f"MID: {type(output_b)=} - {len(output_b)=} - {output_b=}")
print (f"MID: {type(output_s)=} - {len(output_s)=} - {output_s=}")
print (f"OUT: {type(b_output)=} - {len(b_output)=} - {b_output=}")
print (f"OUT: {type(s_output)=} - {len(s_output)=} - {s_output=}")

