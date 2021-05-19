'''
Created on 10 may. 2021

@author: user
'''
from django.test import TestCase
from datastructures.sortedlinkedlist import SortedLinkedList


class TestSortedLinkedList(TestCase):


    def setUp(self):
        self.sll_reverse = SortedLinkedList(reverse=True)
        self.sll_empty = SortedLinkedList()
        self.sll = SortedLinkedList()
        self.sll.insert('A')
        self.sll.insert('B')
        self.sll.insert('C')
        self.sll_reverse.insert('A')
        self.sll_reverse.insert('B')
        self.sll_reverse.insert('C')


    def tearDown(self):
        pass


    def testInsert(self):
        self.assertEqual( self.sll_empty.size(), 0)
        self.assertEqual( self.sll.size(), 3)
        self.sll.insert('D')
        self.assertEqual( self.sll.size(), 4)
        self.assertEqual( self.sll.getByIndex(0).getInfo(), 'A')
        self.assertEqual( self.sll.getByIndex(1).getInfo(), 'B')
        self.assertEqual( self.sll.getByIndex(2).getInfo(), 'C')
        self.assertEqual( self.sll.getByIndex(3).getInfo(), 'D')
        
    
    def testInsertRevers(self):  
        self.assertEqual( self.sll_reverse.size(), 3)
        self.sll_reverse.insert('D')
        self.assertEqual( self.sll_reverse.size(), 4)
        self.assertEqual( self.sll_reverse.getByIndex(0).getInfo(), 'D')
        self.assertEqual( self.sll_reverse.getByIndex(1).getInfo(), 'C')
        self.assertEqual( self.sll_reverse.getByIndex(2).getInfo(), 'B')
        self.assertEqual( self.sll_reverse.getByIndex(3).getInfo(), 'A')    
        
        
    def testRemove(self):
        self.sll.remove('A') 
        self.assertEqual( self.sll.size(), 2)
          